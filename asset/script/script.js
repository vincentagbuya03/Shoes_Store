// Add to Cart Functionality
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('add-to-cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✓ ' + data.message, 'success');
        } else {
            showNotification('✗ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('✗ Error adding item to cart', 'error');
    });
}

// Show notification toast
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Menu Toggle Functionality
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menu-toggle");
  const navLinks = document.getElementById("nav-links");

  // Toggle menu on button click
  if (menuToggle) {
    menuToggle.addEventListener("click", () => {
      navLinks.classList.toggle("show");
    });
  }

  // Close menu when a link is clicked
  navLinks?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      navLinks.classList.remove("show");
    });
  });

  // Add to Cart button handlers
  document.querySelectorAll(".product-card button, .add-to-cart-btn").forEach((button) => {
    button.addEventListener("click", function(e) {
      e.preventDefault();
      
      // Get product ID from data attribute or dataset
      const productId = this.dataset.productId;
      
      if (productId) {
        addToCart(productId, 1);
      } else {
        showNotification('✗ Cannot add item to cart', 'error');
      }
    });
  });

  // Video button scroll handler
  document.querySelector(".video-button")?.addEventListener("click", () => {
    document.getElementById("shop").scrollIntoView({ behavior: "smooth" });
  });

  // Subscribe form handler
  const subscribeForm = document.querySelector(".newsletter-form");
  if (subscribeForm) {
    subscribeForm.addEventListener("submit", function(event) {
      event.preventDefault();
      showNotification('Thank you for subscribing! Check your email for exclusive offers.', 'success');
      this.reset();
    });
  }
});