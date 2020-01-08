
<nav aria-label="Page navigation">
	<ul class="pagination">
	<?foreach($pagination_array as $i => $p):?>
		<?if($p['pfx']):?><li><?=$p['pfx']?></li><?endif?>
		<li<?=($p['class'] ? " class='".$p['class']."'" : "");?>>
        <?if($p['link']):?><a href="<?=$p['link']?>"><?else:?><span><?endif?><?=$p['capt']?><?if(!$p['link']):?></span><?else:?></a><?endif?>
		</li>
		<?endforeach?>
	</ul>
</nav>