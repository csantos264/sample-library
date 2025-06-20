-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS borrowings;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    published_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create borrowings table
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    returned BOOLEAN DEFAULT FALSE,
    returned_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample books
INSERT INTO books (title, author, isbn, description, category, total_copies, available_copies, published_date) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'A story of decadence, excess, and the American Dream in the 1920s.', 'Classic', 5, 5, '1925-04-10'),
('To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'A powerful story of racial injustice and the loss of innocence in the American South.', 'Fiction', 3, 3, '1960-07-11'),
('1984', 'George Orwell', '9780451524935', 'A dystopian novel about totalitarianism, surveillance, and government control.', 'Dystopian', 4, 4, '1949-06-08'),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 'A romantic novel about the emotional development of Elizabeth Bennet.', 'Romance', 6, 6, '1813-01-28'),
('The Hobbit', 'J.R.R. Tolkien', '9780547928227', 'A fantasy novel about the adventures of Bilbo Baggins.', 'Fantasy', 4, 4, '1937-09-21'),
('The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 'A story about alienation and teenage angst in post-war America.', 'Fiction', 3, 3, '1951-07-16'),
('The Lord of the Rings', 'J.R.R. Tolkien', '9780544003415', 'An epic high fantasy novel about the quest to destroy the One Ring.', 'Fantasy', 5, 5, '1954-07-29'),
('The Alchemist', 'Paulo Coelho', '9780062315007', 'A philosophical book about a young shepherd on a journey to find his personal legend.', 'Fiction', 4, 4, '1988-01-01');

-- Create a default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
