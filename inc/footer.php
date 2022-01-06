<?php if ($page != 'admin') { ?>
  <div class="push"></div>
<?php } ?>
  </div>

<?php if ($page != 'admin') { ?>
  <footer class="footer py-5 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6">
            <?php if (getSetting('disable_tos', 'value2') == 0) { ?>
              <a
                href="tos.php"
                class="tos-link"
              ><?= lang('tos'); ?></a>
            <?php } ?>

            <?php if (getSetting('imprint_enable', 'value2') == 1) { ?>
              - <a
                href="imprint.php"
                class="tos-link"
              ><?= lang('imprint'); ?></a>
            <?php } ?>

            <?php if (getSetting('privacy_enable', 'value2') == 1) { ?>
              <br>
              <a
                href="privacy.php"
                class="tos-link"
              ><?= lang('privacy', 'Privacy Policy'); ?></a>
            <?php } ?>
        </div>

        <div
          class="col-12 col-md-6"
          style="text-align: right;"
        >
          <!-- Check if copyright is set to show -->
            <?php if (getSetting('site_copyright', 'value2') == 1) { ?>
              <span style="color: #c10000"><a href="https://www.gmodstore.com/market/view/565" target="_blank">Prometheus</a></span>
                <?= lang('by'); ?>
              Marcuz & Newjorciks<br>
            <?php } ?>
          <i class="fab fa-steam"></i> <?= lang('powered_by', 'Powered By'); ?> <a href="http://steampowered.com">Steam</a><br>

          <!-- Print version number -->
          <span class="version">v.<?= $version; ?></span>
          <!--
              Revision
              246417680
          -->
        </div>
      </div>

      <div class="row">
        <div
          class="col text-right"
          style="margin-top: 5px;"
        >
            <?php if (getSetting('disable_language_selector', 'value2') == 0) { ?>
              <select
                name="language"
                class="selectpicker client_language_picker"
                data-style="btn-prom"
                data-live-search="true"
              >
                  <?php
                  echo options::languages();
                  ?>
              </select>
            <?php } ?>

            <?php if (getSetting('disable_theme_selector', 'value2') == 0) { ?>
              <select
                name="theme"
                class="selectpicker client_theme_picker"
                data-style="btn-prom"
                data-live-search="true"
              >
                <?php
                  echo theme::options();
                ?>
              </select>
            <?php } ?>
        </div>
      </div>
    </div>
  </footer>
<?php } ?>

  <script src='https://cloud.tinymce.com/stable/tinymce.min.js'></script>
  <script src="compiled/js/site.js"></script>

  <script>
    <?php if ($page != 'admin' && getSetting('halloween_things', 'value2') == 1) { ?>
      $.fn.halloweenBats({});
    <?php } ?>
  </script>

  <script
    type="template"
    id="message-danger"
  >
  <p class='bs-callout bs-callout-danger'>
    <button
      type='button'
      class='close'
      data-dismiss='alert'
    >&times;
    </button>
  </p>
  </script>

  <script
    type="template"
    id="message-success"
  >
  <p class='bs-callout bs-callout-success'>
    <button
      type='button'
      class='close'
      data-dismiss='alert'
    >&times;
    </button>
  </p>
  </script>
  </body>
  </html>

<?php
if ($devmode) { ?>
  <div
    class="position-fixed top-right p-3 m-3 bs-callout bs-callout-danger alert"
    style="bottom: 0; right: 0;"
  >
    <i class="fas fa-stopwatch fa-fw"></i>
    <span>
            <?= "Page loaded in: " . (microtime(true) - $time) . "s"; ?>
        </span>
  </div>
<?php } ?>

<?php
//var_dump($db->querys);
