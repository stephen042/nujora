    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#categoriesTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                columnDefs: [{
                        orderable: false,
                        targets: [3, 6]
                    } // disable sorting on Image & Actions columns
                ]
            });
        });
    </script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Confirm before critical actions
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('input[name="product_action"][value="delete"]') ||
                form.querySelector('input[name="report_action"][value="delete"]')) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to perform this action?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>