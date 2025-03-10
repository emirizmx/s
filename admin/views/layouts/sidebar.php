<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2><?php echo SITE_NAME; ?></h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/admin/dashboard" class="<?php echo $route === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/admin/users" class="<?php echo $route === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Kullanıcılar</span>
                </a>
            </li>
            <li>
                <a href="/admin/credits/packages" class="<?php echo $route === 'credits/packages' ? 'active' : ''; ?>">
                    <i class="fas fa-coins"></i>
                    <span>Kredi Paketleri</span>
                </a>
            </li>
            <li>
                <a href="/admin/credits/transactions" class="<?php echo $route === 'credits/transactions' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>İşlemler</span>
                </a>
            </li>
            <li>
                <a href="/admin/games" class="<?php echo $route === 'games' ? 'active' : ''; ?>">
                    <i class="fas fa-gamepad"></i>
                    <span>Oyunlar</span>
                </a>
            </li>
            <li>
                <a href="/admin/prompts" class="<?php echo $route === 'prompts' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span>AI Promptları</span>
                </a>
            </li>
            <li class="nav-dropdown <?php echo in_array($route, ['logs/login', 'logs/system']) ? 'active' : ''; ?>">
                <a href="#" class="dropdown-toggle">
                    <i class="fas fa-history"></i>
                    <span>Loglar</span>
                </a>
                <ul class="nav-dropdown-items">
                    <li>
                        <a href="/admin/logs/login" class="<?php echo $route === 'logs/login' ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Giriş Logları</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/logs/system" class="<?php echo $route === 'logs/system' ? 'active' : ''; ?>">
                            <i class="fas fa-cogs"></i>
                            <span>Sistem Logları</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</aside> 