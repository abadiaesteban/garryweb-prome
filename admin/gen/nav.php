<div class="row">
    <div class="col-12">
        <h2><?= lang('general_settings', 'General Settings'); ?></h2>
    </div>

    <?php if (permissions::has("settings")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=settings">
                <div class="srv-box"><i class="fas fa-cogs fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('settings'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (permissions::has("frontpage")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=frontpage">
                <div class="srv-box"><i class="fas fa-image fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('main_page'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (getSetting('disable_news', 'value2') == 0 && permissions::has("news")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=news">
                <div class="srv-box"><i class="fas fa-rss fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('news'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (permissions::has("tos")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=tos">
                <div class="srv-box"><i class="fas fa-user fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('tos'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (permissions::has("notifications")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=messages">
                <div class="srv-box"><i class="fas fa-comments fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('ingame_notifications'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (permissions::has("imprint")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=imprint">
                <div class="srv-box"><i class="fas fa-stamp fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('imprint', 'Imprint'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>

    <?php if (permissions::has("privacy")) { ?>
        <div class="col-6">
            <a href="admin.php?a=gen&p=privacy">
                <div class="srv-box"><i class="fas fa-file-invoice fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('privacy', 'Privacy Policy'); ?></div>
                </div>
            </a>
        </div>
    <?php } ?>
</div>

<?php if (permissions::has("gateways")) { ?>
    <div class="row">
        <div class="col-12">
            <h2><?= lang('payment_gateways', 'Payment Gateways'); ?></h2>
        </div>

        <div class="col-6">
            <a href="admin.php?a=gen&p=paypal">
                <div class="srv-box"><i class="fab fa-paypal fa-4x fa-fw"></i>

                    <div class="srv-label">PayPal</div>
                </div>
            </a>
        </div>

        <div class="col-6">
            <a href="admin.php?a=gen&p=credits">
                <div class="srv-box"><i class="fas fa-money-bill fa-4x fa-fw"></i>

                    <div class="srv-label">Credits</div>
                </div>
            </a>
        </div>

        <div class="col-6">
            <a href="admin.php?a=gen&p=paymentwall">
                <div class="srv-box"><i class="fas fa-dollar-sign fa-4x fa-fw"></i>

                    <div class="srv-label">Paymentwall</div>
                </div>
            </a>
        </div>

        <div class="col-6">
            <a href="admin.php?a=gen&p=stripe">
                <div class="srv-box"><i class="fab fa-stripe fa-4x fa-fw"></i>

                    <div class="srv-label">Stripe</div>
                </div>
            </a>
        </div>

    </div>
<?php } ?>

<?php if (permissions::has("integration")) { ?>
    <div class="row">
        <div class="col-12">
            <h2><?= lang('integration_settings'); ?></h2>
        </div>

        <div class="col-6">
            <a href="admin.php?a=gen&p=teamspeak">
                <div class="srv-box"><i class="fas fa-cogs fa-4x fa-fw"></i>

                    <div class="srv-label">Teamspeak 3</div>
                </div>
            </a>
        </div>

    </div>
<?php } ?>

<?php if (permissions::has("api")) { ?>
    <div class="row">
        <div class="col-12">
            <h2><?= lang('advanced', 'Advanced'); ?></h2>
        </div>
        <div class="col-6">
            <a href="admin.php?a=gen&p=api">
                <div class="srv-box"><i class="fas fa-code fa-4x fa-fw"></i>

                    <div class="srv-label"><?= lang('api_settings', 'API setttings'); ?></div>
                </div>
            </a>
        </div>
    </div>
<?php } ?>