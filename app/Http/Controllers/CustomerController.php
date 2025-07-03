<?php

namespace App\Http\Controllers;

use App\Models\Customer as Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json($request->user());
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:customers',
            'password' => 'required|string|min:6',
            'phone'    => 'required|digits_between:10,15',
        ]);

        $customer = Customers::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),

        ]);

        $token = $customer->createToken('authen_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'customer'     => $customer,
        ], 201);
    }



    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Tài khoản hoặc mật khẩu không đúng'], 401);
        }

        $customer = Customers::where('email', $request->email)->firstOrFail();
        $token = $customer->createToken('authen_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 200);
    }


    public function show(string $id)
    {
        $customer = Customers::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }
        return response()->json($customer);
    }


    public function destroy(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    // Lấy danh sách tất cả người dùng cho admin
    public function listAll()
    {
        $customers = Customers::all();
        return response()->json($customers);
    }

    // Khoá hoặc mở khoá tài khoản khách hàng
    public function lockUnlock($id, Request $request)
    {
        $customer = Customers::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }

        $request->validate([
            'status' => 'required|in:active,locked,0,1'
        ]);

        $customer->status = $request->status;
        $customer->save();

        return response()->json([
            'message' => $customer->status == 'locked' || $customer->status == 0 ? 'Đã khoá tài khoản' : 'Đã mở khoá tài khoản',
            'customer' => $customer
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        // Kiểm tra user hiện tại có phải admin không
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'bạn không phải admin'], 403);
        }

        $request->validate([
            'role' => 'required|in:user,admin,manager,chef',
        ]);

        $customer = Customers::findOrFail($id);
        $customer->role = $request->role;
        $customer->save();

        return response()->json(['message' => 'Role updated successfully', 'customer' => $customer]);
    }

    // Chỉnh sửa thông tin khách hàng
    public function update(Request $request, $id)
    {
        $customer = Customers::find($id);
        if (!$customer) {
            return response()->json(['message' => 'Không tìm thấy khách hàng'], 404);
        }

        $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|digits_between:10,15',
        ]);

        if ($request->has('name')) {
            $customer->name = $request->name;
        }
        if ($request->has('phone')) {
            $customer->phone = $request->phone;
        }
        $customer->save();

        return response()->json(['message' => 'Cập nhật thông tin thành công', 'customer' => $customer]);
    }
}
