            </main>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('active');
        });
        
        // Responsive table
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            if (table.scrollWidth > table.clientWidth) {
                table.parentElement.style.overflowX = 'auto';
            }
        });
    </script>
</body>
</html> 