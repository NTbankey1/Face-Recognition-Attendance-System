# 📊 Tài liệu Thuyết trình
## Face Recognition Attendance System

---

## Slide 1: Giới thiệu

### Hệ thống điểm danh bằng nhận diện khuôn mặt

- **Hệ thống tự động** xác thực và ghi nhận điểm danh
- Sử dụng công nghệ **nhận diện khuôn mặt** với Deep Learning
- Ứng dụng cho: lớp học, nơi làm việc, sự kiện
- Giảm thiểu thao tác thủ công, tăng độ chính xác

**Mục tiêu:** Tự động hóa quy trình điểm danh, tiết kiệm thời gian và công sức

---

## Slide 2: Vấn đề cần giải quyết

### Những khó khăn hiện tại

❌ **Điểm danh thủ công:**
- Mất nhiều thời gian trong lớp học
- Dễ nhầm lẫn, gian lận
- Khó theo dõi và thống kê

❌ **Các phương pháp truyền thống:**
- Chữ ký → dễ giả mạo
- Thẻ từ → dễ quên, mất
- Vân tay → cần thiết bị riêng

✅ **Giải pháp:** Nhận diện khuôn mặt tự động, nhanh chóng và chính xác

---

## Slide 3: Tính năng chính (Features)

### 📋 Chức năng nổi bật

**1. Phân quyền người dùng:**
- Quản trị viên (Administrator)
- Giảng viên (Lecturer)

**2. Quản lý dữ liệu:**
- Quản lý khóa học, đơn vị học tập
- Quản lý địa điểm (venue)
- Quản lý sinh viên

**3. Nhận diện khuôn mặt:**
- Đăng nhập bằng khuôn mặt
- Điểm danh tự động
- Kiểm tra chất lượng ảnh ngay lập tức

**4. Báo cáo & Logging:**
- Xuất Excel
- Ghi log JSONL cho mọi thao tác

---

## Slide 4: Kiến trúc hệ thống

### 🔄 Công nghệ sử dụng

**Backend:**
- **FastAPI** - API server (Python)
- **YOLOv8n-face** - Phát hiện khuôn mặt
- **ArcFace (R100)** - Tạo embedding và so khớp

**Frontend:**
- **PHP** - Xử lý logic nghiệp vụ
- **JavaScript** - Tương tác người dùng
- **HTML/CSS** - Giao diện

**Database:**
- **MySQL** - Lưu trữ dữ liệu

**Deployment:**
- **Docker** - Containerization
- **Apache/Nginx** - Web server

---

## Slide 5: Kiến trúc nhận diện (Chi tiết)

### 🔄 Quy trình nhận diện

```
┌─────────────┐
│  Frontend   │ ──► Thu thập frame từ camera
│  (Lecturer) │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│   FastAPI Backend   │
│  ┌───────────────┐  │
│  │ YOLO Detection│  │ ──► Phát hiện khuôn mặt
│  └───────┬───────┘  │
│          │          │
│  ┌───────▼────────┐ │
│  │ ArcFace Embed  │ │ ──► Tạo vector đặc trưng
│  └───────┬────────┘ │
│          │          │
│  ┌───────▼────────┐ │
│  │ Cosine Match   │ │ ──► So sánh với dữ liệu
│  └───────┬────────┘ │
└──────────┼──────────┘
           │
           ▼
    ┌──────────────┐
    │   MySQL DB   │ ──► Lưu kết quả điểm danh
    └──────────────┘
```

**API Endpoints:**
- `POST /match` - Nhận diện khuôn mặt
- `POST /reload` - Nạp lại dữ liệu
- `GET /health` - Kiểm tra trạng thái

---

## Slide 6: Quy trình đăng nhập bằng khuôn mặt

### 🔐 Face Login Workflow

**Bước 1: Chuẩn bị dữ liệu**
- Chụp 5 ảnh với các góc/ánh sáng khác nhau
- Kiểm tra chất lượng tự động (blur, ánh sáng)
- Lưu vào `resources/labels/`

**Bước 2: Chạy backend nhận diện**
- Khởi động FastAPI service (port 8001)
- Tự động tải model YOLO và ArcFace

**Bước 3: Đăng nhập**
- Chọn loại người dùng (Admin/Lecturer)
- Nhấn "Đăng nhập bằng khuôn mặt"
- Đưa khuôn mặt vào khung hình

**Bước 4: Ánh xạ & Xác thực**
- Hệ thống tự động ánh xạ nhãn → tài khoản
- Ngưỡng nhận diện: ≥ 0.55 (có thể tùy chỉnh)

---

## Slide 7: Quy trình điểm danh

### 📸 Attendance Recognition Process

**1. Giảng viên khởi động:**
- Chọn khóa học, đơn vị, địa điểm
- Mở tính năng "Face Recognition"

**2. Hệ thống thu thập:**
- Camera chụp frame định kỳ
- Gửi tới backend `/match` endpoint

**3. Xử lý nhận diện:**
- YOLO phát hiện khuôn mặt
- ArcFace tạo embedding
- So khớp với database

**4. Ghi nhận điểm danh:**
- Đánh dấu "Có mặt" khi:
  - Độ tin cậy ≥ 0.4
  - Nhận diện ≥ 2 khung hình liên tiếp
- Cập nhật database tự động

**5. Hiển thị:**
- Khung phát hiện YOLO real-time
- Danh sách nhãn + độ tin cậy
- Bảng điểm danh tự động cập nhật

---

## Slide 8: Kiểm tra chất lượng ảnh

### 📸 Quality Check Features

**Tự động kiểm tra:**
- ✅ **Độ sắc nét (Blur detection)**
- ✅ **Chất lượng ánh sáng**
- ✅ **Độ phân giải khuôn mặt**

**Trạng thái phản hồi:**
- 🟢 **Đạt** - Ảnh chất lượng tốt
- 🟡 **Tạm ổn** - Có thể cải thiện
- 🔴 **Chưa đạt** - Cần chụp lại

**Yêu cầu:**
- Khoảng cách: 0.5 - 1m
- Khuôn mặt tối thiểu: 80px
- Ánh sáng đều 2 bên
- Hạn chế đeo khẩu trang

**Lợi ích:** Đảm bảo chất lượng dữ liệu, tăng độ chính xác nhận diện

---

## Slide 9: Cấu trúc dự án

### 📁 Project Structure

```
Face-Recognition-Attendance-System/
├── database/
│   ├── attendance-db.sql         # SQL schema
│   └── database_connection.php   # DB connection
├── services/
│   └── face_backend/             # FastAPI service
│       ├── main.py
│       ├── requirements.txt
│       └── weights/
│           └── yolov8n-face.pt
├── resources/
│   ├── labels/                   # Ảnh đã đăng ký
│   ├── labels_raw/               # Ảnh gốc
│   ├── logs/                     # JSONL logs
│   ├── assets/                   # CSS, JS
│   └── pages/                    # PHP pages
├── models/                       # Face-API models
└── tools/                        # Utility scripts
```

**Dữ liệu quan trọng:**
- `resources/labels/` - Ảnh đã đăng ký (có augmentation)
- `resources/logs/` - Log hoạt động (Admin/Lecturer)
- `database/` - Schema và connection

---

## Slide 10: Hướng dẫn cài đặt

### 🚀 Setup Procedure

**Cách 1: Docker (Khuyến nghị)**
```bash
# Xem DOCKER.md để dựng nhanh bằng Docker Compose
docker-compose up -d
```

**Cách 2: Cài đặt thủ công**

**1. Clone repository:**
```bash
git clone https://github.com/francis-njenga/Face-Recognition-Attendance-System.git
```

**2. Setup Database:**
- Tạo database `attendance_db`
- Import `database/attendance-db.sql`

**3. Setup Backend:**
```bash
cd services/face_backend
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
wget -O weights/yolov8n-face.pt [URL]
uvicorn main:app --host 0.0.0.0 --port 8001
```

**4. Setup Frontend:**
- Đặt project vào `htdocs/`
- Khởi động Apache và MySQL (XAMPP)

---

## Slide 11: Hướng dẫn sử dụng

### 🧑‍💻 User Guide

**Đăng nhập Quản trị viên:**
- Email: `admin@gmail.com`
- Password: `@admin_`

**Chức năng:**
- Thêm sinh viên (tối thiểu 2 người)
- Chụp 5 ảnh rõ nét cho mỗi sinh viên
- Quản lý khóa học, đơn vị, địa điểm

**Đăng nhập Giảng viên:**
- Email: `mark@gmail.com`
- Password: `@mark_`
- Hoặc tạo tài khoản từ Admin panel

**Chức năng:**
- Chọn khóa học, đơn vị, địa điểm
- Khởi động nhận diện khuôn mặt
- Xuất báo cáo Excel

---

## Slide 12: Logging & Báo cáo

### 📊 Tracking & Reporting

**Logging JSONL:**
- Mọi thao tác được ghi lại tự động
- Format: `YYYY-MM-DD_role_action.jsonl`
- Vị trí: `resources/logs/`

**Ví dụ log:**
```json
{
  "timestamp": "2025-11-07 10:30:00",
  "action": "add_student",
  "user": "admin@gmail.com",
  "details": {...}
}
```

**Báo cáo:**
- Xuất Excel cho từng buổi học
- Thống kê theo khóa học/đơn vị
- Theo dõi lịch sử điểm danh

**Lợi ích:**
- Audit trail đầy đủ
- Dễ dàng phân tích dữ liệu
- Tuân thủ yêu cầu báo cáo

---

## Slide 13: Thông số kỹ thuật

### ⚙️ Technical Specifications

**Performance:**
- Ngưỡng nhận diện: ≥ 0.4 (attendance)
- Ngưỡng đăng nhập: ≥ 0.55 (default)
- Xác nhận: ≥ 2 khung hình liên tiếp

**Models:**
- **YOLOv8n-face** - Phát hiện khuôn mặt
- **ArcFace R100** - Face embedding
- Tự động tải từ InsightFace

**Environment Variables:**
- `FACE_LOGIN_MIN_SCORE` - Ngưỡng đăng nhập (default: 0.55)
- `FACE_STRICT_ENROLLMENT` - Kiểm tra chất lượng nghiêm ngặt

**Infrastructure:**
- Backend: FastAPI (Python 3.x)
- Frontend: PHP 7.4+, Apache/Nginx
- Database: MySQL 5.7+
- Deployment: Docker Compose

---

## Slide 14: Ưu điểm & Lợi ích

### ✅ Advantages

**1. Tự động hóa:**
- Giảm thời gian điểm danh 90%
- Không cần can thiệp thủ công
- Real-time recognition

**2. Độ chính xác:**
- AI-powered recognition
- Kiểm tra chất lượng tự động
- Giảm sai sót và gian lận

**3. Dễ sử dụng:**
- Giao diện trực quan
- Quy trình đơn giản
- Hỗ trợ nhiều ngôn ngữ

**4. Mở rộng:**
- Dễ dàng thêm người dùng
- Hỗ trợ nhiều lớp học
- Logging toàn diện

**5. Bảo mật:**
- Đăng nhập bằng khuôn mặt
- Phân quyền rõ ràng
- Audit trail đầy đủ

---

## Slide 15: Ứng dụng thực tế

### 🎯 Use Cases

**1. Giáo dục:**
- Điểm danh sinh viên trong lớp
- Theo dõi tham dự khóa học
- Báo cáo cho quản lý

**2. Doanh nghiệp:**
- Chấm công nhân viên
- Điểm danh họp
- Kiểm soát ra vào

**3. Sự kiện:**
- Check-in khách mời
- Quản lý tham dự hội thảo
- Thống kê đăng ký

**4. Y tế:**
- Điểm danh bệnh nhân
- Theo dõi nhân viên
- Tuân thủ quy định

**Linh hoạt & Mở rộng cho nhiều ngữ cảnh khác nhau**

---

## Slide 16: Tương lai & Phát triển

### 🚀 Future Improvements

**Cải thiện kỹ thuật:**
- [ ] Nâng cấp model nhận diện
- [ ] Tối ưu tốc độ xử lý
- [ ] Hỗ trợ nhận diện nhiều người cùng lúc
- [ ] Mobile app hỗ trợ

**Tính năng mới:**
- [ ] Nhận diện cảm xúc
- [ ] Cảnh báo vắng mặt
- [ ] Tích hợp với hệ thống quản lý
- [ ] API cho bên thứ 3

**Tối ưu:**
- [ ] Cloud deployment
- [ ] Edge computing
- [ ] Caching mechanisms
- [ ] Load balancing

---

## Slide 17: Kết luận

### 📝 Summary

**Hệ thống điểm danh bằng nhận diện khuôn mặt:**

✅ **Giải pháp hiện đại** sử dụng AI/Deep Learning
✅ **Tự động hóa** quy trình điểm danh
✅ **Chính xác** và **đáng tin cậy**
✅ **Dễ sử dụng** và **triển khai**
✅ **Mở rộng** cho nhiều môi trường

**Kết quả:**
- Tiết kiệm thời gian
- Tăng độ chính xác
- Cải thiện trải nghiệm người dùng
- Hỗ trợ ra quyết định dựa trên dữ liệu

**Sẵn sàng cho production!**

---

## Slide 18: Q&A & Liên hệ

### 💬 Questions & Support

**Tài liệu:**
- README.md - Hướng dẫn tổng quan
- HUONG_DAN_SU_DUNG.md - Hướng dẫn chi tiết
- DOCKER.md - Hướng dẫn Docker

**Hỗ trợ:**
- 📧 Email: [Francis Njenga](mailto:rajeynj@gmail.com)
- 🌐 Website: https://www.frankcodes.tech
- 📦 GitHub: [Face-Recognition-Attendance-System](https://github.com/francis-njenga/Face-Recognition-Attendance-System)

**License:**
- MIT License - Tự do sử dụng và chỉnh sửa

**Cảm ơn đã lắng nghe! 🎉**

---

## Phụ lục: Speaker Notes

### Slide 1 - Giới thiệu
- Giới thiệu vấn đề điểm danh truyền thống
- Nhấn mạnh sự cần thiết của tự động hóa
- Đặt câu hỏi: "Bạn có từng mất thời gian điểm danh không?"

### Slide 2 - Vấn đề
- Kể câu chuyện thực tế về điểm danh thủ công
- So sánh với các phương pháp hiện có
- Dẫn dắt đến giải pháp nhận diện khuôn mặt

### Slide 3 - Features
- Chi tiết từng tính năng
- Demo nếu có thể
- Nhấn mạnh tính thực tế

### Slide 4-5 - Architecture
- Giải thích tại sao chọn YOLO + ArcFace
- So sánh với các giải pháp khác
- Nhấn mạnh performance

### Slide 6-7 - Workflow
- Demo live nếu có thể
- Giải thích từng bước
- Trả lời câu hỏi về accuracy

### Slide 8 - Quality Check
- Demo tính năng kiểm tra chất lượng
- Giải thích tại sao quan trọng
- Hướng dẫn cách chụp ảnh tốt

### Slide 9-10 - Setup
- Hướng dẫn chi tiết
- Troubleshooting common issues
- Tùy chọn Docker vs Manual

### Slide 11 - User Guide
- Demo đăng nhập
- Hướng dẫn từng role
- Best practices

### Slide 12 - Logging
- Giải thích tầm quan trọng
- Demo xem log
- Use cases cho báo cáo

### Slide 13 - Specifications
- Kỹ thuật chi tiết
- Performance benchmarks
- Scaling considerations

### Slide 14 - Advantages
- So sánh với giải pháp khác
- ROI và cost savings
- User testimonials nếu có

### Slide 15 - Use Cases
- Ví dụ thực tế
- Case studies
- Potential applications

### Slide 16 - Future
- Roadmap
- Community contributions
- Feedback welcome

### Slide 17 - Conclusion
- Tóm tắt lại
- Call to action
- Next steps

### Slide 18 - Q&A
- Chuẩn bị trả lời các câu hỏi thường gặp
- Technical questions
- Implementation questions

