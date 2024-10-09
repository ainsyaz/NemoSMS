<div class="header">
    <nav class="navbar navbar-default">
        <div class="container">
            <a class="navbar-brand" style="background-image: url('<?php echo $logo_path; ?>');" href="home.php"></a>
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="myNavbar">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="home.php">HOME</a></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">FEATURES <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php if ($user_role != 'staff'): ?>
                                <li><a href="createUser.php">NEW STAFF</a></li>
                            <?php endif; ?>
                            <?php if ($user_role == 'super'): ?>
                                <li><a href="createAdmin.php">PROMOTE ADMIN</a></li>
                                <li><a href="manage_company.php">MANAGE COMPANY INFORMATION</a></li>
                            <?php endif; ?>
                            <?php if ($user_role == 'super' || $user_role == 'admin'): ?>
                                <li><a href="archive.php">MANAGE STAFF</a></li>
                            <?php endif; ?>
                            <li><a href="profile.php">PERSONAL INFORMATION</a></li>
                            <li><a href="bank.php">BANK DETAILS</a></li>
                            <li><a href="news.php">NEWS / ANNOUNCEMENT</a></li>
                        </ul>
                    </li>
                    <li><a href="logout.php">LOG OUT</a></li>
                </ul>
            </div>
        </div>
    </nav>
</div>