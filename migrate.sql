-- Normalize database for long-term maintainability

-- 1) Charset + Engine
ALTER DATABASE `attendance-db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE `tbladmin`      ENGINE=InnoDB; 
ALTER TABLE `tblattendance` ENGINE=InnoDB; 
ALTER TABLE `tblcourse`     ENGINE=InnoDB; 
ALTER TABLE `tblfaculty`    ENGINE=InnoDB; 
ALTER TABLE `tbllecture`    ENGINE=InnoDB; 
ALTER TABLE `tblstudents`   ENGINE=InnoDB; 
ALTER TABLE `tblunit`       ENGINE=InnoDB; 
ALTER TABLE `tblvenue`      ENGINE=InnoDB;

ALTER TABLE `tbladmin`      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblattendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblcourse`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblfaculty`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tbllecture`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblstudents`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblunit`       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblvenue`      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 2) UNIQUE constraints (avoid duplicates)
ALTER TABLE `tbladmin`   ADD UNIQUE KEY `uq_admin_email` (`emailAddress`);
ALTER TABLE `tbllecture` ADD UNIQUE KEY `uq_lecture_email` (`emailAddress`);
ALTER TABLE `tblstudents` ADD UNIQUE KEY `uq_students_regno` (`registrationNumber`);
ALTER TABLE `tblcourse`  ADD UNIQUE KEY `uq_course_code` (`courseCode`);
ALTER TABLE `tblunit`    ADD UNIQUE KEY `uq_unit_code` (`unitCode`);

-- 3) Fix schema: use courseCode in tblunit (to match project code)
-- Map old numeric Id to courseCode
UPDATE `tblunit` u 
JOIN `tblcourse` c ON u.`courseID` = CAST(c.`Id` AS CHAR)
SET u.`courseID` = c.`courseCode`
WHERE u.`courseID` REGEXP '^[0-9]+$';

-- Rename column to courseCode
ALTER TABLE `tblunit` CHANGE COLUMN `courseID` `courseCode` varchar(50) NOT NULL;

-- 4) FOREIGN KEY constraints and indexes
ALTER TABLE `tblstudents`
  ADD INDEX `idx_students_courseCode` (`courseCode`),
  ADD INDEX `idx_students_faculty` (`faculty`),
  ADD CONSTRAINT `fk_students_course`  FOREIGN KEY (`courseCode`) REFERENCES `tblcourse`(`courseCode`) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_students_faculty` FOREIGN KEY (`faculty`)    REFERENCES `tblfaculty`(`facultyCode`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `tbllecture`
  ADD INDEX `idx_lecture_faculty` (`facultyCode`),
  ADD CONSTRAINT `fk_lecture_faculty` FOREIGN KEY (`facultyCode`) REFERENCES `tblfaculty`(`facultyCode`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `tblunit`
  ADD INDEX `idx_unit_courseCode` (`courseCode`),
  ADD CONSTRAINT `fk_unit_course` FOREIGN KEY (`courseCode`) REFERENCES `tblcourse`(`courseCode`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `tblvenue`
  ADD INDEX `idx_venue_faculty` (`facultyCode`),
  ADD CONSTRAINT `fk_venue_faculty` FOREIGN KEY (`facultyCode`) REFERENCES `tblfaculty`(`facultyCode`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `tblattendance`
  ADD INDEX `idx_att_course_unit_date` (`course`,`unit`,`dateMarked`),
  ADD INDEX `idx_att_student` (`studentRegistrationNumber`),
  ADD CONSTRAINT `fk_att_course`   FOREIGN KEY (`course`)  REFERENCES `tblcourse`(`courseCode`) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_att_unit`     FOREIGN KEY (`unit`)    REFERENCES `tblunit`(`unitCode`) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_att_student`  FOREIGN KEY (`studentRegistrationNumber`) REFERENCES `tblstudents`(`registrationNumber`) ON UPDATE CASCADE ON DELETE RESTRICT;

-- 5) Prevent duplicate attendance per day
ALTER TABLE `tblattendance`
  ADD UNIQUE KEY `uq_att_unique` (`studentRegistrationNumber`,`course`,`unit`,`dateMarked`);
