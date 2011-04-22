<?php
    $i = 0;
    $columns = 5;
?>

<div class="box">
<?php foreach($d['items'] as $controller => $actions): ?>

  <?php $i++ ?>
  <div style="float:left; width:175px;">
    <h2><?php echo ucwords($controller) ?></h2>
    <div style="width:90%;padding-left:0px;padding-bottom:25px">
<?php foreach($actions as $action => $info): ?>
<?php if ($action == 'default') continue ?>
      <div style="margin-bottom:5px">
        <a href="<?php echo $info['url'] ?>"><?php echo $info['title'] ?></a>
      </div>
<?php endforeach ?>
    </div>
  </div>

<?php if ($i % $columns == 0): ?>
  <div style="clear:both"></div>
<?php endif ?>

<?php endforeach ?>
</div>