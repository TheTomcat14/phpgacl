<table class="table table-borderless">
  <tr class="align-middle">
    <td class="text-left">
      <div class="btn-group">
        {if $paging_data.atfirstpage}
        <a class="btn btn-sm btn-primary disabled" href="#"><i class="{#icon_first#}"></i></a>
        <a class="btn btn-sm btn-primary disabled" href="#"><i class="{#icon_previous#}"></i></a>
        {else}
        <a class="btn btn-sm btn-primary" href="{$smarty.server.PHP_SELF}?page=1"><i class="{#icon_first#}"></i></a>
        <a class="btn btn-sm btn-primary" href="{$smarty.server.PHP_SELF}?page={$paging_data.prevpage}"><i class="{#icon_previous#}"></i></a>
        {/if}
      </div>
    </td>
    <td class="text-right">
      <div class="btn-group">
        {if $paging_data.atlastpage}
        <a class="btn btn-sm btn-primary disabled" href="#"><i class="{#icon_next#}"></i></a>
        <a class="btn btn-sm btn-primary disabled" href="#"><i class="{#icon_last#}"></i></a>
        {else}
        <a class="btn btn-sm btn-primary" href="{$smarty.server.PHP_SELF}?page={$paging_data.nextpage}"><i class="{#icon_next#}"></i></a>
        <a class="btn btn-sm btn-primary" href="{$smarty.server.PHP_SELF}?page={$paging_data.lastpageno}"><i class="{#icon_last#}"></i></a>
        {/if}
      </div>
    </td>
  </tr>
</table>
