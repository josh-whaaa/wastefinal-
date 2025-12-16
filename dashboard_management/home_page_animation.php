<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Multi-row Scrolling Slideshow</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 0;
      padding: 20px;
    }

    .slider-row {
      overflow: hidden;
      white-space: nowrap;
      margin: 20px 0;
      position: relative;
    }

	.slider-track {
  display: inline-block;
  animation: scroll-left 60s linear infinite;
}

	.slider-row.reverse .slider-track {
  animation: scroll-right 60s linear infinite;
}

    .slider-track img {
      width: 200px;
      height: 150px;
      object-fit: cover;
      margin-right: 10px;
      border-radius: 8px;
    }
	.slider-track {
		animation-delay: 0s;
	}
	.slider-row.reverse .slider-track {
	animation-delay: 0s;
}


    @keyframes scroll-left {
      0% { transform: translateX(100%); }
      100% { transform: translateX(-100%); }
    }

    @keyframes scroll-right {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }
  </style>
</head>
<body>

<?php
  $images = glob("../images/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
?>

<!-- Row 1 - scroll left -->
<div class="slider-row">
	<div class="slider-track">
		<?php foreach ($images as $img): ?>
			<img src="<?= $img ?>" alt="Slide">
			<?php endforeach; ?>
			<?php foreach ($images as $img): // duplicate for seamless loop ?>
			<img src="<?= $img ?>" alt="Slide">
		<?php endforeach; ?>
	</div>
</div>

<!-- Row 2 - scroll right -->
<div class="slider-row reverse">
  <div class="slider-track">
  <?php foreach ($images as $img): ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
<?php foreach ($images as $img): // duplicate for seamless loop ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
  </div>
</div>
<!-- Row 3 - scroll right -->
<div class="slider-row">
  <div class="slider-track">
  <?php foreach ($images as $img): ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
<?php foreach ($images as $img): // duplicate for seamless loop ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
  </div>
</div>
<!-- Row 4 - scroll right -->
<div class="slider-row reverse">
  <div class="slider-track">
  <?php foreach ($images as $img): ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
<?php foreach ($images as $img): // duplicate for seamless loop ?>
  <img src="<?= $img ?>" alt="Slide">
<?php endforeach; ?>
  </div>
</div>

</body>
</html>
