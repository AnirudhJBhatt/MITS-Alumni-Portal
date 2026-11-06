-- ==============================================================
-- MITS Alumni Portal - Sample Seed Data
-- ==============================================================

USE `mits_alumni_portal`;

-- Insert Initial Admin and Sample Alumni Users
INSERT INTO `users` (`id`, `google_id`, `email`, `full_name`, `avatar_url`, `role`, `status`) VALUES
(1, 'google_admin_001', 'admin@mits.ac.in', 'MITS Portal Administrator', 'https://ui-avatars.com/api/?name=Admin+MITS&background=1e3a8a&color=ffffff&bold=true&size=128', 'admin', 'verified'),
(2, 'google_alumni_002', 'kavya.sharma@example.com', 'Kavya Sharma', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150', 'alumni', 'verified'),
(3, 'google_alumni_003', 'rahul.reddy@example.com', 'Rahul Reddy', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150', 'alumni', 'verified'),
(4, 'google_alumni_004', 'priya.nair@example.com', 'Priya Nair', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150', 'alumni', 'verified')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Insert Alumni Profiles
INSERT INTO `alumni_profiles` (`user_id`, `roll_number`, `department`, `graduation_year`, `current_company`, `designation`, `industry`, `location`, `linkedin_url`, `bio`, `skills`, `is_mentor`) VALUES
(2, '17691A0589', 'Computer Science & Engineering', 2021, 'Google', 'Software Engineer III', 'Information Technology', 'Bengaluru, India', 'https://linkedin.com', 'Proud MITS CSE alumna passionate about distributed backend systems, cloud architectures, and mentoring young developers.', 'Java, Spring Boot, Kubernetes, Go, GCP, System Design', 1),
(3, '15691A0452', 'Electronics & Communication Engineering', 2019, 'Microsoft', 'Senior Cloud Solutions Architect', 'Cloud Computing', 'Hyderabad, India', 'https://linkedin.com', 'Leading enterprise cloud modernization at Azure. Always eager to connect with fellow MITSians!', 'Azure, Terraform, Microservices, Python, CI/CD', 1),
(4, '16691A0314', 'Mechanical Engineering', 2020, 'Tata Motors', 'Design & Simulation Engineer', 'Automotive', 'Pune, India', 'https://linkedin.com', 'Working on next-generation electric vehicle powertrain simulation and structural integrity.', 'SolidWorks, ANSYS, FEA, Product Lifecycle Management', 0)
ON DUPLICATE KEY UPDATE `department` = VALUES(`department`);

-- Insert Sample Job Listings
INSERT INTO `jobs` (`id`, `posted_by`, `title`, `company`, `location`, `job_type`, `experience_level`, `description`, `requirements`, `apply_link`, `status`) VALUES
(1, 2, 'Software Engineer - Backend', 'Google', 'Bengaluru / Hybrid', 'full-time', '1-3 Years', 'Join the core platform engineering team building low-latency, scalable APIs for global users.', 'Strong fundamentals in Data Structures, Algorithms, and proficiency in Java or Go.', 'https://careers.google.com', 'active'),
(2, 3, 'Cloud Support Engineer (Intern)', 'Microsoft', 'Hyderabad, India', 'internship', 'Fresh Graduate (2025/2026)', 'Great opportunity for final-year students looking to gain hands-on production experience in enterprise cloud environments.', 'Basic understanding of Linux/Windows OS, networking concepts, and scripting.', 'https://careers.microsoft.com', 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Insert Sample Feed Posts
INSERT INTO `posts` (`id`, `user_id`, `content`, `post_type`, `likes_count`, `comments_count`) VALUES
(1, 2, 'Thrilled to share that our team at Google Bengaluru is expanding! We have openings for software engineers. Feel free to reach out directly to me if you would like a referral or resume review.', 'hiring', 14, 3),
(2, 3, 'Proud to see the amazing progress MITS has made over the years. Planning a weekend virtual session on "Preparing for Cloud & Systems Architecture Roles" for 3rd and 4th year students. Drop a comment below if interested!', 'general', 28, 7)
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- Insert Sample Events
INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `venue`, `cover_image`, `created_by`) VALUES
(1, 'OSAM Annual Alumni Meet 2025', 'Annual reunion of Muthoot Institute of Technology and Science alumni bringing together over 300 graduates across batches for an evening of networking, awards, and nostalgia.', '2025-12-20', 'MITS Campus Auditorium, Varikoli', 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&auto=format&fit=crop&q=80', 1),
(2, 'MITS Convocation & Graduation Ceremony 2024', 'Celebrating the graduating batch of 2024 with degree distribution, keynote addresses by industry leaders, and felicitation of academic toppers.', '2024-07-15', 'Main Open Air Theatre, MITS', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&auto=format&fit=crop&q=80', 1),
(3, 'National Tech Symposium & Hackathon - TechVantage', 'Inter-collegiate technical hackathon and symposium where MITS alumni served as judges, mentors, and keynote speakers on AI and Cloud Computing.', '2024-10-12', 'Visvesvaraya Hall & Innovation Lab, MITS', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&auto=format&fit=crop&q=80', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Insert Sample Event Photos
INSERT INTO `event_photos` (`id`, `event_id`, `photo_url`, `caption`, `uploaded_by`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1000&auto=format&fit=crop&q=80', 'Alumni reunion keynote address', 1),
(2, 1, 'https://images.unsplash.com/photo-1528605248644-14dd04022da1?w=1000&auto=format&fit=crop&q=80', 'Batch of 2018 group photograph', 1),
(3, 1, 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1000&auto=format&fit=crop&q=80', 'OSAM awards felicitation ceremony', 1),
(4, 1, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1000&auto=format&fit=crop&q=80', 'Cultural evening and musical performance', 1),
(5, 2, 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1000&auto=format&fit=crop&q=80', 'Graduates tossing caps in celebration', 1),
(6, 2, 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1000&auto=format&fit=crop&q=80', 'Department of Computer Science graduation group', 1),
(7, 2, 'https://images.unsplash.com/photo-1562774053-701939374585?w=1000&auto=format&fit=crop&q=80', 'MITS campus administrative quadrangle', 1),
(8, 3, 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1000&auto=format&fit=crop&q=80', 'Alumni panel discussion on emerging tech', 1),
(9, 3, 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1000&auto=format&fit=crop&q=80', 'Mentoring student teams during 24-hr hackathon', 1),
(10, 3, 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1000&auto=format&fit=crop&q=80', 'Winners felicitation by alumni judges', 1)
ON DUPLICATE KEY UPDATE `photo_url` = VALUES(`photo_url`);
