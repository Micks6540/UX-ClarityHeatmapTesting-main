// simple toggles for showing signup/signin
function showSignup() {
    document.getElementById('signin').style.display = 'none';
    document.getElementById('signup').style.display = 'block';
    document.getElementById('signup').setAttribute('aria-hidden', 'false');
    document.getElementById('signin').setAttribute('aria-hidden', 'true');
}

function showSignin() {
    document.getElementById('signup').style.display = 'none';
    document.getElementById('signin').style.display = 'block';
    document.getElementById('signup').setAttribute('aria-hidden', 'true');
    document.getElementById('signin').setAttribute('aria-hidden', 'false');
}

// on page load: ensure proper default state
document.addEventListener('DOMContentLoaded', function(){
    if (!document.getElementById('signin') || !document.getElementById('signup')) return;
    // default: show signin
    document.getElementById('signin').style.display = 'block';
    document.getElementById('signup').style.display = 'none';
});

function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
        sidebar.classList.toggle("open");
    }
}
// Add this function to your existing script.js
function makeBooksClickable() {
    const bookItems = document.querySelectorAll('.book-item');
    
    bookItems.forEach(item => {
        // Remove any existing click listeners to avoid duplicates
        item.removeEventListener('click', handleBookClick);
        
        // Add new click listener
        item.addEventListener('click', handleBookClick);
        
        // Make sure cursor is pointer
        item.style.cursor = 'pointer';
    });
}

function handleBookClick(e) {
    // Don't trigger if clicking on buttons inside
    if (e.target.closest('button') || e.target.closest('.book-actions')) {
        return;
    }
    
    // Try to find book ID in different ways
    let bookId = null;
    
    // Method 1: Check for hidden input
    const hiddenInput = this.querySelector('input[name="book_id"]');
    if (hiddenInput) {
        bookId = hiddenInput.value;
    }
    
    // Method 2: Check for data attribute
    if (!bookId && this.dataset.bookId) {
        bookId = this.dataset.bookId;
    }
    
    // Method 3: Try to extract from onclick attribute
    if (!bookId) {
        const borrowBtn = this.querySelector('.borrow-now');
        if (borrowBtn && borrowBtn.onclick) {
            const onclickStr = borrowBtn.getAttribute('onclick');
            const match = onclickStr.match(/borrowNow\((\d+)\)/);
            if (match) {
                bookId = match[1];
            }
        }
    }
    
    // If we found a book ID, redirect
    if (bookId) {
        window.location.href = 'book_details.php?id=' + bookId;
    }
}

// Call this when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    makeBooksClickable();
    
    // Also call it after AJAX loads or dynamic content
    setTimeout(makeBooksClickable, 100);
});

