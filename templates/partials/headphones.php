<?php
/**
 * The product, drawn. Parts carry data-part so the scroll scene can animate them,
 * and colours come from CSS variables so the finish picker can recolour it.
 * @var string $id unique id prefix
 */
?>
<svg class="headphones" viewBox="0 0 400 380" role="img" aria-labelledby="<?= e($id) ?>-title">
  <title id="<?= e($id) ?>-title">Halo One headphones</title>
  <path data-part="band" class="hp-band" d="M78 222 V178 a122 122 0 0 1 244 0 V222" pathLength="1"/>
  <path data-part="band-inner" class="hp-band-inner" d="M104 196 v-18 a96 96 0 0 1 192 0 v18" pathLength="1"/>
  <g data-part="cup-left">
    <rect class="hp-yoke" x="66" y="206" width="24" height="44" rx="10"/>
    <rect class="hp-shell" x="34" y="232" width="92" height="128" rx="44"/>
    <rect class="hp-cushion" x="92" y="246" width="26" height="100" rx="13"/>
  </g>
  <g data-part="cup-right">
    <rect class="hp-yoke" x="310" y="206" width="24" height="44" rx="10"/>
    <rect class="hp-shell" x="274" y="232" width="92" height="128" rx="44"/>
    <rect class="hp-cushion" x="282" y="246" width="26" height="100" rx="13"/>
  </g>
</svg>
