USE `attendance-db`;
-- set admin passwords
UPDATE tbladmin SET password='$2y$12$T8OpysurFmMSrX4liPEQL.wtbcS/aBugLsaTmZvK9CwmyykvPnVf2' WHERE emailAddress IN ('admin@gmail.com','ntbankey1122005@gmail.com','thaibaobook@gmail.com');
-- set lecturer passwords
UPDATE tbllecture SET password='$2y$12$j9T0dkFGY8F0ouQ3xk41XuY.ngR6N/5cJbgn1voE88SM/BLxwV3eS' WHERE emailAddress IN ('mark@gmail.com','ntbankey1122005@gmail.com','thaibaobook@gmail.com');
