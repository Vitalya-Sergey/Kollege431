    </div>
    <footer class="footer mt-5 py-5 bg-dark text-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-4 text-white">О проекте "Коллеги"</h5>
                    <p class="mb-4">Платформа для размещения и просмотра видеоконтента колледжей России. Мы объединяем образовательные учреждения и создаем единое информационное пространство.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-vk fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-telegram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-4 text-white">Контактная информация</h5>
                    <ul class="list-unstyled footer-info">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            641400, Россия, г. Карасук,<br>
                            ул. Фрунзе, 89.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:+79138987456" class="text-light text-decoration-none">+7 (913) 898-74-56</a>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:info@kollege.ru" class="text-light text-decoration-none">info@kollege.ru</a>
                        </li>
                        <li>
                            <i class="fas fa-clock me-2"></i>
                            Пн-Пт: 9:00 - 18:00
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-4 text-white">Полезные ссылки</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">Правила использования</a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">Политика конфиденциальности</a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">Помощь</a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">Для колледжей</a>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4 border-secondary">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">© <?php echo date('Y'); ?> Коллеги. Все права защищены.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-muted small">
                        Свидетельство о регистрации СМИ: ЭЛ № ФС 77-12345 от 01.01.2023
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <style>
    .footer {
        background: linear-gradient(135deg, #212529 0%, #343a40 100%);
    }
    .footer-info li {
        display: flex;
        align-items: start;
    }
    .footer-info li i {
        margin-top: 4px;
    }
    .social-links a {
        transition: all 0.3s ease;
    }
    .social-links a:hover {
        opacity: 0.8;
        transform: translateY(-2px);
    }
    .footer a:hover {
        color: #0dcaf0 !important;
    }
    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }
        .footer-info li {
            justify-content: center;
        }
        .social-links {
            justify-content: center;
            display: flex;
        }
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 