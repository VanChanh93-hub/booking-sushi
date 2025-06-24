lấy ra danh sách món ăn GET 
http://127.0.0.1:8000/api/food


==============================


đăng nhập bằng mail GET
http://127.0.0.1:8000/api/auth/google/redirect
http://127.0.0.1:8000/api/auth/google/callback




=======================


Quên mật khẩu POST
http://127.0.0.1:8000/api/forgot-password // gửi mail
{
    "email": "nhapmail"
}

  http://127.0.0.1:8000/api/reset-password POST
  {
  "email": "user@example.com",
  "code": "codemail",
  "password": "newpassword"
}

====================
Đặt bàn không kèm món
  http://127.0.0.1:8000/api/orders/bookTables   POST
{
    "customer_id": 1,
    "guest_count": 4,
    "reservation_date": "2024-01-25",
    "reservation_time": "18:00:00",
    "payment_method": "cash",
    "total_price": 500000,
    "note": "bàn sinh nhật"
}

Đặt bàn kèm món và combo
  http://127.0.0.1:8000/api/orders/bookTables   POST



{
    "customer_id": 1,
    "guest_count": 6,
    "reservation_date": "2025-01-25",
    "reservation_time": "19:00:00",
    "payment_method": "momo",
    "total_price": 850000,
    "note": "bàn sinh nhật",
    "foods": [
        {
            "food_id": 1,
            "quantity": 2,
            "price": 150000
        },
        {
            "food_id": 2,
            "quantity": 1,
            "price": 200000
        }
    ],
    "combos": [
        {
            "combo_id": 1,
            "quantity": 1,
            "price": 500000
        }
    ]
}


==================
lấy danh sách giờ và bàn trống dựa vào số ngày GET

http://127.0.0.1:8000/api/tables/available-times?reservation_date=2025-07-02

===========================================


lấy danh sách món ăn theo category và food_group của food GET
http://127.0.0.1:8000/api/foods/category/{id_cate}/groups

======================
 Đăng nhập POST
http://127.0.0.1:8000/api/login

 {
     "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "phone": "0123456789"
}
======================
 Đăng Ký POST
{
    "email": "test@example.com",
    "password": "password123"
}

=====================
xem lịch sử dơn hàng 
http://127.0.0.1:8000/api/orders/history/{id_customer} GET


huỷ đơn hàng 
http://127.0.0.1:8000/api/orders/cancel/{id_order} POST


