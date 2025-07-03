<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Food;
use App\Models\UserPreference;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'in:user,model',
            'messages.*.text' => 'required|string'
        ]);

        // Lấy thực đơn
        $menu = Food::all()->pluck('name')->toArray();
        $menuText = "Thực đơn nhà hàng hiện có: " . implode(", ", $menu) . ".";

        // Lấy sở thích người dùng (nếu đã đăng nhập)
        $userId =  $request->customer_id ?? null;
        $pref = UserPreference::where('customer_id', $userId)->first();

        $liked = $pref && $pref->liked_ingredients ? implode(", ", $pref->liked_ingredients) : "Chưa rõ";
        $disliked = $pref && $pref->disliked_ingredients ? implode(", ", $pref->disliked_ingredients) : "Chưa rõ";

        // Prompt hệ thống tùy chỉnh
        $systemPrompt = [
            'role' => 'user',
            'parts' => [[
                'text' => "Bạn là trợ lý ẩm thực của nhà hàng sushi Takami. Hãy tư vấn món phù hợp với sở thích khách hàng.\n"
                    . "$menuText\n\n"
                    . "Khách hàng thích: $liked\n"
                    . "Không thích: $disliked"
            ]]
        ];

        // Ghép đoạn chat
        $conversation = [$systemPrompt];
        foreach ($request->messages as $msg) {
            $conversation[] = [
                'role' => $msg['role'],
                'parts' => [['text' => $msg['text']]]
            ];

            // Nếu là tin nhắn của người dùng → phân tích sở thích
            if ($msg['role'] === 'user' && $userId) {
                $prefs = $this->extractPreferencesFromText($msg['text']);

                if (!empty($prefs['liked']) || !empty($prefs['disliked'])) {
                    $pref = UserPreference::firstOrCreate(['customer_id' => $userId]);

                    $updatedLikes = array_unique(array_merge($pref->liked_ingredients ?? [], $prefs['liked']));
                    $updatedDislikes = array_unique(array_merge($pref->disliked_ingredients ?? [], $prefs['disliked']));

                    $pref->update([
                        'liked_ingredients' => $updatedLikes,
                        'disliked_ingredients' => $updatedDislikes
                    ]);
                }
            }
        }

        // Gửi lên Gemini
        $response = Http::post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'),
            ['contents' => $conversation]
        );

        return response()->json([
            'reply' => $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không hiểu.'
        ]);
    }
    private function extractPreferencesFromText(string $text): array
    {
        $liked = [];
        $disliked = [];

        // Từ khóa đơn giản
        $like_keywords = ['thích', 'ưa thích', 'yêu', 'muốn ăn'];
        $dislike_keywords = ['không thích', 'ghét', 'dị ứng', 'không ăn được', 'không ăn cay được', 'không ăn cay', 'tránh', 'không muốn'];

        // Thực đơn hiện tại để đối chiếu
        $foods = Food::all()->pluck('name')->toArray();

        foreach ($like_keywords as $kw) {
            if (str_contains($text, $kw)) {
                foreach ($foods as $food) {
                    if (str_contains($text, $food)) {
                        $liked[] = $food;
                    }
                }
            }
        }

        foreach ($dislike_keywords as $kw) {
            if (str_contains($text, $kw)) {
                foreach ($foods as $food) {
                    if (str_contains($text, $food)) {
                        $disliked[] = $food;
                    }
                }
            }
        }

        return [
            'liked' => array_unique($liked),
            'disliked' => array_unique($disliked),
        ];
    }
}