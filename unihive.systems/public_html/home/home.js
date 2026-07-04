document.addEventListener("DOMContentLoaded", function () {
  // Existing animation code
  let observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
          if (entry.isIntersecting) {
              entry.target.classList.add("show"); 
          }
      });
  }, { threshold: 0.6 });

  document.querySelectorAll(".card").forEach((element) => {
      observer.observe(element);
  });

  // Notification System
  const bellIcon = document.getElementById('bellIcon');
  const notificationDropdown = document.getElementById('notificationDropdown');
  const notificationCount = document.getElementById('notificationCount');
  const notificationList = document.getElementById('notificationList');
  const markAllReadBtn = document.getElementById('markAllRead');

  let isDropdownOpen = false;
  let lastNotificationCheck = 0;
  let notificationsLoaded = false;

  // Function to load notifications with refresh protection
  function loadNotifications(force = false) {
    const now = Date.now();
    // Prevent frequent refreshes (minimum 5 seconds between refreshes)
    if (!force && (now - lastNotificationCheck < 5000) && notificationsLoaded) {
      return;
    }
    
    lastNotificationCheck = now;
    
    fetch('../notifications/get_notifications.php')
      .then(response => response.json())
      .then(data => {
        notificationsLoaded = true;
        
        if (data.unread_count > 0) {
          notificationCount.textContent = data.unread_count;
          notificationCount.style.display = 'block';
        } else {
          notificationCount.style.display = 'none';
        }
        
        // Clear existing notifications
        notificationList.innerHTML = '';
        
        if (data.notifications.length === 0) {
          notificationList.innerHTML = '<div class="notification-empty">No notifications</div>';
        } else {
          data.notifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = `notification-item ${notification.is_read == 0 ? 'unread' : ''}`;
            notificationItem.dataset.id = notification.notification_id;
            
            notificationItem.innerHTML = `
              <div class="notification-title">${notification.title}</div>
              <div class="notification-message">${notification.message}</div>
              <div class="notification-time">${notification.formatted_date}</div>
            `;
            
            notificationItem.addEventListener('click', function() {
              markAsRead(notification.notification_id);
              this.classList.remove('unread');
            });
            
            notificationList.appendChild(notificationItem);
          });
        }
      })
      .catch(error => console.error('Error loading notifications:', error));
  }
  
  // Function to mark notification as read
  function markAsRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    
    fetch('../notifications/mark_as_read.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadNotifications(); // Refresh notifications
        }
      })
      .catch(error => console.error('Error marking notification as read:', error));
  }
  
  // Function to mark all notifications as read
  function markAllAsRead() {
    const formData = new FormData();
    formData.append('mark_all', 1);
    
    fetch('../notifications/mark_as_read.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadNotifications(); // Refresh notifications
          
          // Remove unread class from all notification items
          document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
          });
        }
      })
      .catch(error => console.error('Error marking all notifications as read:', error));
  }
  
  // Toggle notification dropdown with force refresh
  bellIcon.addEventListener('click', function(e) {
    e.stopPropagation();
    isDropdownOpen = !isDropdownOpen;
    
    if (isDropdownOpen) {
      notificationDropdown.style.display = 'block';
      loadNotifications(true); // Force refresh notifications when opening dropdown
    } else {
      notificationDropdown.style.display = 'none';
    }
  });
  
  // Mark all as read button
  markAllReadBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    markAllAsRead();
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (isDropdownOpen && !notificationDropdown.contains(e.target) && e.target !== bellIcon) {
      notificationDropdown.style.display = 'none';
      isDropdownOpen = false;
    }
  });
  
  // Initialize notifications with browser storage to prevent duplicates on page refresh
  document.addEventListener('DOMContentLoaded', function() {
    // Check if browser storage has notification data to prevent duplicates
    const lastLoad = localStorage.getItem('lastNotificationLoad');
    const now = Date.now();
    
    if (lastLoad && (now - parseInt(lastLoad) < 5000)) {
      // Recently loaded, use cached data if available
      const cachedCount = localStorage.getItem('notificationCount');
      if (cachedCount) {
        if (parseInt(cachedCount) > 0) {
          notificationCount.textContent = cachedCount;
          notificationCount.style.display = 'block';
        }
        notificationsLoaded = true;
      }
    } else {
      // Load fresh data
      loadNotifications();
      localStorage.setItem('lastNotificationLoad', now.toString());
    }
    
    // Check for new notifications every 30 seconds instead of refresh on load
    setInterval(() => {
      loadNotifications();
      localStorage.setItem('lastNotificationLoad', Date.now().toString());
    }, 30000);
  });
});

// Particles.js code (existing)
particlesJS("particles-js", {
    particles: {
      number: { value: 80, density: { enable: true, value_area: 800 } },
      color: { value: "#ffffff" },
      shape: {
        type: "circle",
        stroke: { width: 0, color: "#000000" },
        polygon: { nb_sides: 5 },
        image: { src: "img/github.svg", width: 100, height: 100 }
      },
      opacity: {
        value: 0.5,
        random: false,
        anim: { enable: false, speed: 1, opacity_min: 0.1, sync: false }
      },
      size: {
        value: 3,
        random: true,
        anim: { enable: false, speed: 40, size_min: 0.1, sync: false }
      },
      line_linked: {
        enable: true,
        distance: 150,
        color: "#ffffff",
        opacity: 0.4,
        width: 1
      },
      move: {
        enable: true,
        speed: 6,
        direction: "none",
        random: false,
        straight: false,
        out_mode: "out",
        bounce: false,
        attract: { enable: false, rotateX: 600, rotateY: 1200 }
      }
    },
    interactivity: {
      detect_on: "canvas",
      events: {
        onhover: { enable: true, mode: "repulse" },
        onclick: { enable: true, mode: "push" },
        resize: true
      },
      modes: {
        grab: { distance: 400, line_linked: { opacity: 1 } },
        bubble: { distance: 400, size: 40, duration: 2, opacity: 8, speed: 3 },
        repulse: { distance: 200, duration: 0.4 },
        push: { particles_nb: 4 },
        remove: { particles_nb: 2 }
      }
    },
    retina_detect: true
  });
  var count_particles, stats, update;
  stats = new Stats();
  stats.setMode(0);
  stats.domElement.style.position = "absolute";
  stats.domElement.style.left = "0px";
  stats.domElement.style.top = "0px";
  document.body.appendChild(stats.domElement);
  count_particles = document.querySelector(".js-count-particles");
  update = function () {
    stats.begin();
    stats.end();
    if (window.pJSDom[0].pJS.particles && window.pJSDom[0].pJS.particles.array) {
      count_particles.innerText = window.pJSDom[0].pJS.particles.array.length;
    }
    requestAnimationFrame(update);
  };
  requestAnimationFrame(update);



