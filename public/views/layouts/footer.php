    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center">
                        <i class="fas fa-globe text-white text-sm"></i>
                    </div>
                    <span class="font-semibold text-gray-900">Muniverse</span>
                </div>
                
                <p class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> Muniverse. All rights reserved.
                </p>
                
                <div class="flex gap-4">
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition">
                        <i class="fab fa-facebook text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
        
        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('.animate-fade-in');
            flashMessages.forEach(msg => {
                if (msg.closest('.max-w-7xl')) {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-10px)';
                    msg.style.transition = 'all 0.3s ease';
                    setTimeout(() => msg.remove(), 300);
                }
            });
        }, 5000);
    </script>
</body>
</html>
