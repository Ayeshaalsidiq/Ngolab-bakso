    </div> <!-- End Page Content -->
</div> <!-- End Wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Toggle Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var el = document.getElementById("menu-toggle");
        if(el) {
            el.addEventListener("click", function(e) {
                e.preventDefault();
                document.getElementById("wrapper").classList.toggle("toggled");
            });
        }
    });

    // Simple script to auto-add 'active' class to sidebar based on current URL
    document.addEventListener("DOMContentLoaded", function() {
        var currentUrl = window.location.href;
        var listItems = document.querySelectorAll("#sidebar-wrapper .list-group-item");
        listItems.forEach(function(item) {
            if(currentUrl.includes(item.getAttribute("href"))) {
                item.classList.add("active");
            } else {
                item.classList.remove("active");
            }
        });
    });
</script>
</body>
</html>
