<div class="modal fade" id="log_exportModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('ExportLog') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form id="exportForm" action="" method="post">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          echo $inputs;
          ?>
          <fieldset title="<?php echo translate('ChooseLogSelection') ?>">
            <legend><?php echo translate('SelectLog') ?></legend>
            <label for="selectorAll"><?php echo translate('All') ?></label>
            <input type="radio" id="selectorAll" name="selector" value="all"/>
            <label for="selectorFilter"><?php echo translate('Filter') ?></label>
            <input type="radio" id="selectorFilter" name="selector" value="filter"/>
            <label for="selectorCurrent"><?php echo translate('Current') ?></label>
            <input type="radio" id="selectorCurrent" name="selector" value="current" data-validators="validate-one-required"/>
          </fieldset>
          <fieldset title="<?php echo translate('ChooseLogFormat') ?>">
            <legend><?php echo translate('SelectFormat') ?></legend>
            <label for="formatText">TXT</label><input type="radio" id="formatText" name="format" value="text"/>
            <label for="formatTSV">TSV</label><input type="radio" id="formatTSV" name="format" value="tsv"/>
            <label for="formatXML">HTML</label><input type="radio" id="formatHTML" name="format" value="html"/>
            <label for="formatXML">XML</label><input type="radio" id="formatXML" name="format" value="xml" class="validate-one-required"/>
          </fieldset>
          <div id="exportError">
            <?php echo translate('ExportFailed') ?>: <span id="exportErrorText"></span>
          </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="exportButton" value="Export" data-on-click="exportRequest"><?php echo translate('Export') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

