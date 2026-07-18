
document.addEventListener('DOMContentLoaded', function() {
	// Dark/Light mode toggle
	const darkModeSetting = 'zoomblog_dark_mode';
	const savedMode = localStorage.getItem(darkModeSetting);

	function applyMode(mode) {
		if (mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
			document.body.classList.add('dark-mode');
		} else {
			document.body.classList.remove('dark-mode');
		}
	}

	applyMode(savedMode || 'light');

	// RTL support check
	if (document.documentElement.dir === 'rtl') {
		document.body.classList.add('rtl');
	}

	// Reading Progress Bar
	const progressBar = document.querySelector('.reading-progress-bar');
	if (progressBar) {
		function updateProgressBar() {
			const scrollTop = window.scrollY;
			const docHeight = document.documentElement.scrollHeight - window.innerHeight;
			let scrollPercent = 0;
			if (docHeight > 0) {
				scrollPercent = (scrollTop / docHeight) * 100;
			}
			progressBar.style.width = scrollPercent + '%';
		}

		window.addEventListener('scroll', updateProgressBar);
		updateProgressBar();
	}

	// Reading History
	function addToReadingHistory() {
		const postId = document.body.classList.toString().match(/postid-(\d+)/);
		if (!postId) return;

		const historyKey = 'zoomblog_reading_history';
		let history = JSON.parse(localStorage.getItem(historyKey) || '[]');
		
		// Remove existing entry if present
		history = history.filter(id => id != postId[1]);
		
		// Add to beginning of array
		history.unshift(postId[1]);
		
		// Keep only last 20 entries
		if (history.length > 20) {
			history = history.slice(0, 20);
		}
		
		localStorage.setItem(historyKey, JSON.stringify(history));
	}

	// Check if we're on a single post page
	if (document.body.classList.contains('single')) {
		addToReadingHistory();
	}

	// Bookmark Functionality
	const bookmarksKey = 'zoomblog_bookmarks';

	function getBookmarks() {
		return JSON.parse(localStorage.getItem(bookmarksKey) || '[]');
	}

	function saveBookmarks(bookmarks) {
		localStorage.setItem(bookmarksKey, JSON.stringify(bookmarks));
	}

	function isBookmarked(postId) {
		const bookmarks = getBookmarks();
		return bookmarks.includes(String(postId));
	}

	function toggleBookmark(postId) {
		let bookmarks = getBookmarks();
		if (isBookmarked(postId)) {
			bookmarks = bookmarks.filter(id => id != String(postId));
		} else {
			bookmarks.push(String(postId));
		}
		saveBookmarks(bookmarks);
		updateBookmarkButtons();
	}

	function updateBookmarkButtons() {
		const buttons = document.querySelectorAll('.bookmark-button');
		buttons.forEach(button => {
			const postId = button.getAttribute('data-post-id');
			if (isBookmarked(postId)) {
				button.textContent = 'Bookmarked';
				button.classList.add('bookmarked');
			} else {
				button.textContent = 'Bookmark';
				button.classList.remove('bookmarked');
			}
		});
	}

	// Add click event listeners to bookmark buttons
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('bookmark-button')) {
			const postId = e.target.getAttribute('data-post-id');
			toggleBookmark(postId);
		}
		if (e.target.classList.contains('remove-bookmark')) {
			const postId = e.target.getAttribute('data-post-id');
			toggleBookmark(postId);
			// Remove from DOM
			const item = e.target.closest('.bookmark-item');
			if (item) {
				item.remove();
			}
		}
	});

	// Initialize bookmark buttons on page load
	updateBookmarkButtons();
});
