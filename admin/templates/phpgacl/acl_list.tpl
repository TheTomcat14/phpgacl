{include file="phpgacl/header.tpl"}
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
<body>
{include file="phpgacl/navigation.tpl"}
<form method="get" name="acl_list" id="acl_list" action="acl_list.php">
<table class="table table-sm table-bordered text-center">
  <tr class="font-weight-bold">
    <td colspan="6">Filter</td>
  </tr>
  <tr class="thead-dark">
    <th width="12%">&nbsp;</th>
    <th width="22%">ACO</th>
    <th width="22%">ARO</th>
    <th width="22%">AXO</th>
    <th width="22%" colspan="2">ACL</th>
  </tr>
  <tr class="align-middle text-center">
    <td class="text-left"><strong>Section:</strong> </td>
    <td>
    <select name="filter_aco_section" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_aco_sections selected=$filter_aco_section}
    </select>
    </td>
    <td>
    <select name="filter_aro_section" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_aro_sections selected=$filter_aro_section}
    </select>
    </td>
    <td>
    <select name="filter_axo_section" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_axo_sections selected=$filter_axo_section}
    </select>
    </td>
    <td colspan="2">
    <select name="filter_acl_section" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_acl_sections selected=$filter_acl_section}
    </select>
    </td>
  </tr>
  <tr class="align-middle text-center">
    <td class="text-left"><strong>Object:</strong> </td>
    <td><input type="text" name="filter_aco" size="20" value="{$filter_aco}" class="form-control form-control-sm"></td>
    <td><input type="text" name="filter_aro" size="20" value="{$filter_aro}" class="form-control form-control-sm"></td>
    <td><input type="text" name="filter_axo" size="20" value="{$filter_axo}" class="form-control form-control-sm"></td>
    <td class="text-left" width="11%"><strong>Allow:</strong> </td>
    <td class="text-left" width="11%">
     <select name="filter_allow" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_allow selected=$filter_allow}
    </select>
    </td>
  </tr>
  <tr class="align-middle text-center">
    <td class="text-left"><strong>Group:</strong> </td>
    <td>&nbsp;</td>
    <td><input type="text" name="filter_aro_group" size="20" value="{$filter_aro_group}" class="form-control form-control-sm"></td>
    <td><input type="text" name="filter_axo_group" size="20" value="{$filter_axo_group}" class="form-control form-control-sm"></td>
    <td class="text-left"><strong>Enabled:</strong> </td>
    <td class="text-left">
    <select name="filter_enabled" tabindex="0" class="form-control form-control-sm">
      {html_options options=$options_filter_enabled selected=$filter_enabled}
    </select>
    </td>
  </tr>
  <tr class="align-middle text-left">
  <td><strong>Return&nbsp;Value:</strong> </td>
  <td colspan="5"><input type="text" name="filter_return_value" size="50" value="{$filter_return_value}" class="form-control form-control-sm"></td>
  </tr>
  <tr class="table-secondary text-center">
    <td colspan="6">
      <!-- <input type="submit" class="btn btn-sm btn-primary" name="action" value="Filter"> -->
      <button type="submit" form="acl_list" class="btn btn-sm btn-primary" name="action" value="Filter">
        <i class="{#icon_filter#}"></i> Filter
      </button>
      </td>
  </tr>
</table>
<br>
<table class="table table-sm table-bordered">
  <tr class="pager">
  <td colspan="8">
    {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?action=$action&filter_aco_section=$filter_aco_section&filter_aco=$filter_aco&filter_aro_section=$filter_aro_section&filter_aro=$filter_aro&filter_axo_section=$filter_axo_section&filter_axo=$filter_axo&filter_aro_group=$filter_aro_group&filter_axo_group=$filter_axo_group&filter_return_value=$filter_return_value&filter_allow=$filter_allow&filter_enabled=$filter_enabled&"}
  </td>
  </tr>
  <tr class="thead-dark">
    <th width="2%">ID</th>
    <th width="24%">ACO</th>
    <th width="24%">ARO</th>
    <th width="24%">AXO</th>
    <th width="10%">Access</th>
    <th width="10%">Enabled</th>
    <th width="4%">Functions</th>
    <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
  </tr>

{foreach from=$acls item=acl}
  {cycle assign=class values="table-default,table-secondary"}
  <tr>
    <td class="align-middle text-center {$class}" rowspan="3">{$acl.id}</td>
    <td class="align-top text-left {$class}">
  {if count($acl.aco) gt 0}
    <ul>
    {foreach from=$acl.aco key=section item=objects}
      <li>{$section}<ol>
      {foreach from=$objects item=obj}
        <li>{$obj}</li>
      {/foreach}
      </ol></li>
    {/foreach}
    </ul>
  {else}
    &nbsp;
  {/if}
    </td>
    <td class="align-top text-left {$class}">
    {if count($acl.aro) gt 0}
    <ul>
      {foreach from=$acl.aro key=section item=objects}
      <li>{$section}<ol>
      {foreach from=$objects item=obj}
        <li>{$obj}</li>
      {/foreach}
      </ol></li>
      {/foreach}
    </ul>
    {if count($acl.aro_groups) gt 0}
    <div class="divider"></div>
    {/if}
    {/if}
    {if count($acl.aro_groups) gt 0}
    <strong>Groups</strong><ol>
      {foreach from=$acl.aro_groups item=group}
      <li>{$group}</li>
      {/foreach}
    </ol>
    {/if}
    </td>
    <td class="align-top text-left {$class}">
    {if count($acl.axo) gt 0}
    <ul>
      {foreach from=$acl.axo key=section item=objects}
      <li>{$section}<ol>
      {foreach from=$objects item=obj}
        <li>{$obj}</li>
      {/foreach}
      </ol></li>
      {/foreach}
    </ul>
    {if count($acl.axo_groups) gt 0}
    <div class="divider"></div>
    {/if}
    {/if}
    {if count($acl.axo_groups) gt 0}
    <strong>Groups</strong><ol>
      {foreach from=$acl.axo_groups item=group}
      <li>{$group}</li>
      {/foreach}
    </ol>
    {/if}
    </td>
    <td class="align-middle text-center {if $acl.allow}table-success{else}table-danger{/if}">
    {if $acl.allow}
      ALLOW
    {else}
      DENY
    {/if}
    </td>
    <td class="align-middle text-center {if $acl.enabled}table-success{else}table-danger{/if}">
    {if $acl.enabled}
      Yes
    {else}
      No
    {/if}
    </td>
    <td class="align-middle text-center {$class}" rowspan="3">
        <a class="btn btn-sm btn-warning" href="acl_admin.php?action=edit&acl_id={$acl.id}&return_page={$return_page}">
          <i class="{#icon_edit#}"></i> Edit
        </a>
    </td>
    <td class="align-middle text-center {$class}" rowspan="3">
        <input type="checkbox" class="checkbox" name="delete_acl[]" value="{$acl.id}">
    </td>
  </tr>

  <tr>
    <td class="align-top text-left {$class}" colspan="3">
        <strong>Return Value:</strong> {$acl.return_value}
    </td>
    <td class="align-middle text-center {$class}" colspan="2">
        {$acl.section_name}
    </td>
  </tr>
  <tr>
    <td class="align-top text-left {$class}" colspan="3">
        <strong>Note:</strong> {$acl.note}
    </td>
    <td class="align-middle text-center {$class}" colspan="2">
        {$acl.updated_date|date_format:"%d-%b-%Y&nbsp;%H:%M:%S"}
    </td>
  </tr>
{/foreach}
  <tr class="pager">
  <td colspan="8">
    {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?action=$action&filter_aco_section=$filter_aco_section&filter_aco=$filter_aco&filter_aro_section=$filter_aro_section&filter_aro=$filter_aro&filter_axo_section=$filter_axo_section&filter_axo=$filter_axo&filter_aro_group=$filter_aro_group&filter_axo_group=$filter_axo_group&filter_return_value=$filter_return_value&filter_allow=$filter_allow&filter_enabled=$filter_enabled&"}
  </td>
  </tr>
  <tr class="table-secondary">
    <td colspan="6">&nbsp;</td>
    <td colspan="2" class="text-center">
      <!-- <input type="submit" class="btn btn-sm btn-danger" name="action" value="Delete"> -->
      <button type="submit" class="btn btn-sm btn-danger" name="action" value="Delete">
        <i class="{#icon_delete#}"></i> Delete
      </button>
    </td>
  </tr>
</table>
<input type="hidden" name="return_page" value="{$return_page}">
</form>
{include file="phpgacl/footer.tpl"}
