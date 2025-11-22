# 👥 Phân Công Công Việc
## Face Recognition Attendance System
### Dự án cho 4 thành viên nhóm

---

## 📋 Tổng Quan Phân Công

### Nguyên tắc phân công:
- **Rõ ràng**: Mỗi người có trách nhiệm cụ thể
- **Độc lập**: Có thể làm việc song song, giảm xung đột
- **Bổ trợ**: Có điểm giao thoa để tích hợp tốt
- **Cân bằng**: Khối lượng công việc tương đương

---

## 👤 Người 1: Backend AI/ML Engineer
### 🔬 Trách nhiệm: Face Recognition Backend

**Vai trò:** Phát triển và tối ưu hệ thống nhận diện khuôn mặt

---

### 📦 Công việc chính:

#### 1. FastAPI Backend Service
- [ ] **Xây dựng API endpoints:**
  - `POST /match` - Nhận diện khuôn mặt từ frame
  - `POST /quality` - Kiểm tra chất lượng ảnh
  - `POST /reload` - Nạp lại dữ liệu embedding
  - `GET /health` - Kiểm tra trạng thái service

- [ ] **Tích hợp YOLOv8n-face:**
  - Cài đặt và cấu hình model
  - Tải trọng số `yolov8n-face.pt`
  - Xử lý phát hiện khuôn mặt từ video frame
  - Trả về bounding boxes

- [ ] **Tích hợp ArcFace:**
  - Cài đặt InsightFace library
  - Load model `arcface_r100_v1`
  - Tạo face embeddings
  - So khớp cosine similarity

#### 2. Face Recognition Logic
- [ ] **Xử lý dữ liệu:**
  - Load embeddings từ thư mục `resources/labels/`
  - Xây dựng index để tìm kiếm nhanh
  - Cache embeddings để tăng tốc

- [ ] **Thuật toán so khớp:**
  - Cosine similarity calculation
  - Threshold management (0.4 cho attendance, 0.55 cho login)
  - Xử lý nhiều khuôn mặt trong một frame

- [ ] **Quality Check:**
  - Blur detection (Laplacian variance)
  - Brightness assessment
  - Face size validation (tối thiểu 80px)

#### 3. File Structure
```
services/face_backend/
├── main.py                 # FastAPI application
├── requirements.txt        # Python dependencies
├── .env                    # Environment variables
├── weights/
│   └── yolov8n-face.pt    # YOLO model weights
├── utils/
│   ├── face_detector.py   # YOLO face detection
│   ├── face_encoder.py    # ArcFace encoding
│   ├── matcher.py         # Cosine matching logic
│   └── quality_check.py   # Image quality assessment
└── Dockerfile             # Container configuration
```

#### 4. Performance Optimization
- [ ] Tối ưu tốc độ xử lý frame
- [ ] Batch processing cho nhiều faces
- [ ] Caching mechanisms
- [ ] Memory management

#### 5. Testing
- [ ] Unit tests cho từng module
- [ ] Integration tests cho API endpoints
- [ ] Performance benchmarking
- [ ] Accuracy testing với test dataset

---

### 🎯 Deliverables (Sản phẩm):
1. ✅ FastAPI service chạy ổn định trên port 8001
2. ✅ API documentation (Swagger/OpenAPI)
3. ✅ Test results và accuracy metrics
4. ✅ Docker container cho backend service

---

### 📚 Kỹ năng cần thiết:
- Python, FastAPI
- Deep Learning (YOLO, ArcFace)
- Computer Vision
- API design & testing

---

### ⏱️ Ước tính thời gian:
- **Tuần 1-2:** Setup và tích hợp YOLO + ArcFace
- **Tuần 3:** Xây dựng matching logic và quality check
- **Tuần 4:** Testing và tối ưu

---

### 🔗 Giao tiếp với:
- **Người 2:** API contract cho `/match` endpoint
- **Người 4:** Deploy backend service

---

## 👤 Người 2: Frontend Lecturer Developer
### 🎨 Trách nhiệm: Lecturer Interface & Attendance

**Vai trò:** Phát triển giao diện giảng viên và chức năng điểm danh

---

### 📦 Công việc chính:

#### 1. Lecturer Dashboard
- [ ] **Trang chủ (Home):**
  - Form chọn Course, Unit, Venue
  - Validation input
  - Lưu session data

- [ ] **Giao diện điểm danh:**
  - Video stream từ camera
  - Canvas overlay hiển thị bounding boxes
  - Real-time recognition display
  - Attendance table tự động cập nhật

#### 2. Face Recognition Integration
- [ ] **Camera handling:**
  - Access webcam với `getUserMedia()`
  - Capture frames định kỳ (interval)
  - Send frames đến `/match` endpoint
  - Error handling cho camera permissions

- [ ] **API Communication:**
  - Gọi `POST /match` với base64 image
  - Xử lý response (labels, scores, bboxes)
  - Display recognition results
  - Handle errors và retries

- [ ] **Recognition Display:**
  - Vẽ bounding boxes lên canvas
  - Hiển thị label và confidence score
  - Màu sắc theo confidence level
  - History list của recognitions

#### 3. Attendance Management
- [ ] **Attendance table:**
  - Hiển thị danh sách sinh viên
  - Tự động đánh dấu "Có mặt" khi nhận diện
  - Logic: ≥ 0.4 confidence trong ≥ 2 frames liên tiếp
  - Manual override (thêm/xóa thủ công)

- [ ] **Attendance persistence:**
  - Lưu attendance vào database
  - Ghi log JSONL cho mỗi buổi điểm danh
  - Timestamp và metadata

#### 4. Face Login Feature
- [ ] **Login by face:**
  - UI cho face login
  - Capture face image
  - Send to `/match` với user type
  - Handle authentication response
  - Session management

#### 5. File Structure
```
resources/pages/lecture/
├── home.php                  # Lecturer dashboard
├── attendance.php            # Attendance interface
├── view-attendance.php       # View attendance records
└── export.php                # Export to Excel

resources/assets/javascript/
├── face_logics/
│   ├── script.js             # Main recognition logic
│   ├── camera.js             # Camera handling
│   └── api_client.js         # API communication
└── attendance.js             # Attendance table management

resources/assets/css/
└── lecturer_styles.css       # Lecturer-specific styles
```

#### 6. UI/UX Enhancement
- [ ] Responsive design
- [ ] Loading indicators
- [ ] Error messages
- [ ] Success notifications
- [ ] Service status indicator

#### 7. Export Functionality
- [ ] Excel export
  - Generate Excel file với attendance data
  - Format theo khóa học/buổi học
  - Include metadata (date, venue, course)

---

### 🎯 Deliverables (Sản phẩm):
1. ✅ Lecturer dashboard hoàn chỉnh
2. ✅ Real-time attendance recognition
3. ✅ Face login functionality
4. ✅ Excel export feature
5. ✅ Responsive UI với good UX

---

### 📚 Kỹ năng cần thiết:
- PHP (backend logic)
- JavaScript (ES6+)
- HTML/CSS
- Canvas API
- WebRTC (camera access)
- AJAX/Fetch API

---

### ⏱️ Ước tính thời gian:
- **Tuần 1:** Lecturer dashboard và basic UI
- **Tuần 2:** Camera integration và API calls
- **Tuần 3:** Attendance logic và real-time updates
- **Tuần 4:** Face login, export, và polish UI

---

### 🔗 Giao tiếp với:
- **Người 1:** API contract và response format
- **Người 3:** Database schema cho attendance records
- **Người 4:** Testing integration

---

## 👤 Người 3: Admin & Database Developer
### 🗄️ Trách nhiệm: Admin Interface & Database

**Vai trò:** Phát triển giao diện quản trị và quản lý database

---

### 📦 Công việc chính:

#### 1. Database Design & Management
- [ ] **Database schema:**
  - Tạo/update `attendance-db.sql`
  - Tables: `tbladmin`, `tbllecture`, `tblstudent`, `tblcourse`, `tblunit`, `tblvenue`, `tblattendance`
  - Table `face_login_map` cho face authentication
  - Indexes cho performance

- [ ] **Database connection:**
  - Maintain `database_connection.php`
  - Error handling
  - Connection pooling (nếu cần)

- [ ] **Data management:**
  - Sample data (`sample_data.sql`)
  - Migration scripts
  - Backup procedures

#### 2. Admin Dashboard
- [ ] **Home page:**
  - Statistics overview
  - Recent activities
  - Quick actions

- [ ] **Student Management:**
  - Add/Edit/Delete students
  - Upload student photos (5 images per student)
  - Image quality validation
  - Bulk operations

- [ ] **Course & Unit Management:**
  - CRUD operations cho courses
  - CRUD operations cho units
  - Assignments và relationships

- [ ] **Venue Management:**
  - Create/Edit/Delete venues
  - Venue information
  - Images và descriptions

- [ ] **Lecturer Management:**
  - Add/Edit/Delete lecturers
  - Assign courses/units
  - Permission management

#### 3. Face Enrollment Features
- [ ] **Photo capture interface:**
  - Webcam capture cho 5 images
  - Real-time quality check (gọi `/quality` endpoint)
  - Preview và retake functionality
  - Upload và augmentation

- [ ] **Image processing:**
  - Save to `resources/labels/` và `resources/labels_raw/`
  - Automatic augmentation (flip, rotate, brightness)
  - Organization theo student ID/email

- [ ] **Quality validation:**
  - Integrate với backend `/quality` endpoint
  - Display quality feedback
  - Prevent saving low-quality images

#### 4. Logging System
- [ ] **JSONL logging:**
  - Log all admin actions
  - Format: `YYYY-MM-DD_role_action.jsonl`
  - Structure: timestamp, action, user, details
  - Save to `resources/logs/admin/`

- [ ] **Log viewing:**
  - View logs by date
  - Filter by action type
  - Search functionality

#### 5. File Structure
```
resources/pages/administrator/
├── home.php                  # Admin dashboard
├── manage-students.php       # Student CRUD
├── manage-course.php         # Course management
├── manage-lecture.php        # Lecture management
├── create-venue.php          # Venue management
├── handle_delete.php         # Delete operations
└── includes/
    ├── sidebar.php           # Navigation sidebar
    └── topbar.php            # Header bar

resources/assets/javascript/
├── admin_functions.js        # Admin-specific functions
├── addCourse.js              # Course management
├── addLecture.js             # Lecture management
└── delete_request.js         # Delete confirmations

database/
├── attendance-db.sql         # Main schema
├── database_connection.php   # DB connection
├── sample_data.sql           # Sample data
└── migrate.sql               # Migration scripts
```

#### 6. Authentication & Authorization
- [ ] **Login system:**
  - Traditional login (email/password)
  - Face login integration
  - Session management
  - Password hashing

- [ ] **Role-based access:**
  - Admin vs Lecturer permissions
  - Route protection
  - Feature access control

#### 7. Reports & Analytics
- [ ] **Attendance reports:**
  - View attendance by course/unit/venue
  - Date range filtering
  - Statistics và charts

---

### 🎯 Deliverables (Sản phẩm):
1. ✅ Complete database schema
2. ✅ Full admin dashboard với CRUD operations
3. ✅ Student enrollment với face capture
4. ✅ Logging system
5. ✅ Authentication system

---

### 📚 Kỹ năng cần thiết:
- PHP
- MySQL/SQL
- Database design
- JavaScript (frontend)
- HTML/CSS

---

### ⏱️ Ước tính thời gian:
- **Tuần 1:** Database design và setup
- **Tuần 2:** Admin dashboard và student management
- **Tuần 3:** Course/venue management và face enrollment
- **Tuần 4:** Logging, reports, và polish

---

### 🔗 Giao tiếp với:
- **Người 1:** Database schema cho face_login_map
- **Người 2:** API cho attendance data
- **Người 4:** Database migration và deployment

---

## 👤 Người 4: Integration & DevOps Engineer
### 🔧 Trách nhiệm: Integration, Testing & Deployment

**Vai trò:** Tích hợp các module, testing, và deployment

---

### 📦 Công việc chính:

#### 1. System Integration
- [ ] **Frontend-Backend integration:**
  - Đảm bảo API calls hoạt động đúng
  - Error handling toàn hệ thống
  - Data flow validation

- [ ] **Frontend-Database integration:**
  - PHP-Database connections
  - Data consistency
  - Transaction management

- [ ] **Cross-module communication:**
  - Admin → Lecturer data flow
  - Student enrollment → Recognition system
  - Attendance → Reporting

#### 2. Routing & Configuration
- [ ] **Router setup:**
  - Maintain `router.php`
  - URL routing logic
  - Route protection

- [ ] **Configuration files:**
  - `.htaccess` configuration
  - Apache/Nginx configs
  - Environment variables

- [ ] **Session management:**
  - PHP session configuration
  - Security settings
  - Cross-page state management

#### 3. Docker & Deployment
- [ ] **Docker setup:**
  - `docker-compose.yml` configuration
  - Dockerfile cho web service
  - Dockerfile cho face backend
  - Multi-container orchestration

- [ ] **Deployment scripts:**
  - `run_full_stack.sh` - Full stack startup
  - Database initialization scripts
  - Service health checks

- [ ] **Configuration management:**
  - Environment variables
  - Service ports configuration
  - Volume mounts

#### 4. Testing
- [ ] **Unit testing:**
  - PHP unit tests
  - Python unit tests (cho backend)
  - JavaScript tests

- [ ] **Integration testing:**
  - API integration tests
  - Database integration tests
  - End-to-end workflow tests

- [ ] **System testing:**
  - Full system workflow
  - Edge cases
  - Performance testing
  - Security testing

- [ ] **User acceptance testing:**
  - Test scenarios
  - Bug tracking
  - Issue resolution

#### 5. Documentation
- [ ] **Technical documentation:**
  - API documentation
  - Database schema documentation
  - Setup guides

- [ ] **User documentation:**
  - Update README.md
  - Create `HUONG_DAN_SU_DUNG.md`
  - Create `DOCKER.md`

- [ ] **Developer documentation:**
  - Code comments
  - Architecture diagrams
  - Contribution guidelines

#### 6. Security & Performance
- [ ] **Security:**
  - SQL injection prevention
  - XSS protection
  - CSRF tokens
  - Authentication security
  - File upload security

- [ ] **Performance:**
  - Database query optimization
  - Caching strategies
  - Image optimization
  - Frontend optimization

#### 7. Error Handling & Logging
- [ ] **Error handling:**
  - Global error handlers
  - User-friendly error messages
  - Error logging

- [ ] **Monitoring:**
  - Service health monitoring
  - Performance monitoring
  - Error tracking

#### 8. File Structure
```
./
├── docker-compose.yml        # Multi-container setup
├── router.php                # URL routing
├── .htaccess                 # Apache config
├── index.php                 # Entry point
├── run_full_stack.sh         # Startup script

services/web/
├── Dockerfile                # Web container
└── apache-vhost.conf         # Apache vhost

tools/
├── prepare_images.py         # Image preprocessing
└── test_match.py             # Testing utilities
```

---

### 🎯 Deliverables (Sản phẩm):
1. ✅ Fully integrated system
2. ✅ Docker deployment setup
3. ✅ Complete test suite
4. ✅ Comprehensive documentation
5. ✅ Security hardening
6. ✅ Performance optimization

---

### 📚 Kỹ năng cần thiết:
- DevOps (Docker, CI/CD)
- Testing (Unit, Integration, E2E)
- System integration
- Security best practices
- Documentation
- Troubleshooting

---

### ⏱️ Ước tính thời gian:
- **Tuần 1:** Docker setup và basic integration
- **Tuần 2:** Testing framework và integration tests
- **Tuần 3:** Documentation và security audit
- **Tuần 4:** Performance optimization và final deployment

---

### 🔗 Giao tiếp với:
- **Tất cả thành viên:** Integration và testing
- **Người 1:** Backend deployment
- **Người 2:** Frontend deployment
- **Người 3:** Database deployment

---

## 📅 Timeline Chung

### Tuần 1: Foundation
- **Người 1:** Setup YOLO + ArcFace
- **Người 2:** Lecturer dashboard UI
- **Người 3:** Database schema + Admin UI
- **Người 4:** Docker setup + Routing

### Tuần 2: Core Features
- **Người 1:** Matching logic + Quality check
- **Người 2:** Camera + API integration
- **Người 3:** Student enrollment + Face capture
- **Người 4:** Integration testing

### Tuần 3: Advanced Features
- **Người 1:** Optimization + Testing
- **Người 2:** Attendance logic + Face login
- **Người 3:** Logging + Reports
- **Người 4:** Documentation + Security

### Tuần 4: Polish & Deploy
- **Người 1:** Final testing + API docs
- **Người 2:** UI polish + Export
- **Người 3:** Final features + Testing
- **Người 4:** Deployment + Final testing

---

## 🤝 Quy Trình Làm Việc

### Communication
1. **Daily standup:** 15 phút mỗi ngày để sync
2. **Weekly meeting:** Review progress và plan tuần sau
3. **Git workflow:**
   - Main branch: stable code
   - Feature branches: `feature/name-of-feature`
   - Pull requests cho code review

### Code Standards
- **PHP:** PSR-12 coding standard
- **JavaScript:** ESLint configuration
- **Python:** PEP 8 style guide
- **Comments:** Inline comments cho logic phức tạp

### Git Commit Messages
```
feat: Add face recognition API endpoint
fix: Fix camera permission error
docs: Update README with setup instructions
test: Add unit tests for face matcher
refactor: Optimize database queries
```

### Issue Tracking
- Tạo issues trên GitHub/GitLab
- Assign cho từng thành viên
- Track progress và milestones

---

## 📊 Milestones & Checkpoints

### Milestone 1: Foundation (Cuối tuần 1)
- ✅ Backend service chạy được
- ✅ Database schema hoàn chỉnh
- ✅ Basic UI cho Admin và Lecturer
- ✅ Docker setup cơ bản

### Milestone 2: Core Features (Cuối tuần 2)
- ✅ Face recognition hoạt động
- ✅ Camera integration
- ✅ Student enrollment với face capture
- ✅ Basic attendance tracking

### Milestone 3: Advanced Features (Cuối tuần 3)
- ✅ Face login functionality
- ✅ Complete admin dashboard
- ✅ Logging system
- ✅ Excel export

### Milestone 4: Production Ready (Cuối tuần 4)
- ✅ Full integration
- ✅ Testing completed
- ✅ Documentation complete
- ✅ Deployment ready

---

## 🚨 Rủi Ro & Giải Pháp

### Rủi Ro Tiềm Ẩn:
1. **API không tương thích:** Người 1 và 2 cần thống nhất API contract sớm
2. **Database schema thay đổi:** Người 3 và 4 cần communication tốt
3. **Performance issues:** Cần testing sớm và optimize
4. **Integration bugs:** Người 4 cần testing thường xuyên

### Giải Pháp:
- ✅ API contract document từ đầu
- ✅ Database schema review định kỳ
- ✅ Performance testing sớm
- ✅ Integration testing hàng ngày

---

## ✅ Definition of Done

Một task được coi là hoàn thành khi:
- ✅ Code đã viết và test
- ✅ Không có lỗi khi chạy
- ✅ Code review đã pass
- ✅ Documentation đã update
- ✅ Đã merge vào main branch

---

## 📞 Contact & Support

**Project Lead:** [Tên leader]  
**Repository:** [GitHub URL]  
**Communication:** [Slack/Discord/Teams link]

---

**Chúc cả nhóm làm việc hiệu quả và thành công! 🚀**

