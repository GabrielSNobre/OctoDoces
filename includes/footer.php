        </main> <!-- Fecha a tag main aberta no header -->

        <footer id="contact">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>Sobre Nós</h3>
                        <p>A Octo Doces nasceu do amor por doces e da vontade de trazer sabores especiais para momentos especiais.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Contato</h3>
                        <p><i class="fas fa-map-marker-alt"></i> Rua dos Doces, 123 - Sigma</p>
                        <p><i class="fas fa-phone"></i> (00) 1234-5678</p>
                        <p><i class="fas fa-envelope"></i> contato@octodoces.com</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Redes Sociais</h3>
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="copyright">
                    <p>&copy; <?= date('Y') ?> Octo Doces. Todos os direitos reservados.</p>
                </div>
            </div>
        </footer>

        <script>
            // Fechar mensagens flutuantes
            document.querySelectorAll('.fechar-mensagem').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.parentElement.style.display = 'none';
                });
            });

            // Menu dropdown do usuário
            const menuUsuario = document.querySelector('.menu-usuario');
            if (menuUsuario) {
                menuUsuario.addEventListener('click', (e) => {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        menuUsuario.classList.toggle('aberto');
                    }
                });
            }

            // Atualizar contador do carrinho periodicamente (para usuários logados)
            <?php if ($usuarioLogado): ?>
            function atualizarContadorCarrinho() {
                fetch('api/contador_carrinho.php')
                    .then(response => response.json())
                    .then(data => {
                        const contador = document.querySelector('.carrinho-contador');
                        if (contador) {
                            contador.textContent = data.quantidade;
                        }
                    });
            }
            
            // Atualizar a cada 30 segundos
            setInterval(atualizarContadorCarrinho, 30000);
            <?php endif; ?>
        </script>
        <?= isset($jsExtra) ? $jsExtra : '' ?>
    </body>
</html>