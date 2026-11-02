        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <span>Simulasi Praktikum ERP &bull; Semester Ganjil 2026/2027</span> &bull; 
                    <span>Prodi Teknologi Rekayasa Perangkat Lunak (TRPL), Politeknik META Industri</span>
                </div>
                <div style="text-align: right;">
                    <span>Dosen Pengampu: Adie Kusna Wibowo</span> &bull;
                    <span>Database: <?= dbDriver() === 'mysql' ? 'MySQL' : 'SQLite 3' ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
