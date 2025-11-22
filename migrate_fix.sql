-- Fix UPDATE collation issue by joining numerically, then continue schema updates

-- Map old numeric courseID in tblunit to courseCode from tblcourse
UPDATE `tblunit` u 
JOIN `tblcourse` c ON CAST(u.`courseID` AS UNSIGNED) = c.`Id`
SET u.`courseID` = c.`courseCode`
WHERE u.`courseID` REGEXP '^[0-9]+$';

-- Rename column courseID -> courseCode
ALTER TABLE `tblunit` CHANGE COLUMN `courseID` `courseCode` varchar(50) NOT NULL;

-- Ensure referenced parent columns are UNIQUE for FKs
ALTER TABLE `tblfaculty` ADD UNIQUE KEY `uq_faculty_code` (`facultyCode`);

-- Foreign keys and indexes (should not be present yet due to previous abort)
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
  ADD CONSTRAINT `fk_att_student`  FOREIGN KEY (`studentRegistrationNumber`) REFERENCES `tblstudents`(`registrationNumber`) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD UNIQUE KEY `uq_att_unique` (`studentRegistrationNumber`,`course`,`unit`,`dateMarked`);
