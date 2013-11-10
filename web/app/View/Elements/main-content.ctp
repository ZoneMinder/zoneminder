<div id="main-content" class="container">

    <div class="row">

      <div class="col-sm-2 col-md-2 col-lg-2 sidebar-offcanvas" id="sidebar">
        <div class="sidebar-nav">
          <?php echo $this->fetch('sidebar'); ?>
        </div>
      </div> <!-- End Sidebar -->

      <div class="col-sm-10 col-md-10 col-lg-10" id="main-content-body">
        <?php echo $this->Session->flash(); ?>
    
        <?php echo $this->fetch('content'); ?>
      </div> <!-- End Main Content Body -->

    </div> <!-- End Row -->

</div> <!-- End Main Content -->
