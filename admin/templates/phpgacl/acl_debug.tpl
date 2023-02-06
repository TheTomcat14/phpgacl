{include file="phpgacl/header.tpl"}
  </head>
<body>
{include file="phpgacl/navigation.tpl"}
<form method="get" name="acl_debug" action="acl_debug.php">
<table class="table table-bordered table-sm">
  <tr class="thead-dark">
    <th rowspan="2">&nbsp;</th>
    <th colspan="2">ACO</th>
    <th colspan="2">ARO</th>
    <th colspan="2">AXO</th>
    <th rowspan="2">Root ARO<br>Group ID</th>
    <th rowspan="2">Root AXO<br>Group ID</th>
    <th rowspan="2">&nbsp;</th>
  </tr>
  <tr class="thead-dark">
    <th>Section</th>
    <th>Value</th>
    <th>Section</th>
    <th>Value</th>
    <th>Section</th>
    <th>Value</th>
  </tr>
  <tr class="align-middle text-center">
    <td class="text-nowrap"><strong>acl_query(</strong></td>
    <td><input class="form-control form-control-sm" type="text" name="aco_section_value" size="15" value="{$aco_section_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="aco_value" size="15" value="{$aco_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="aro_section_value" size="15" value="{$aro_section_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="aro_value" size="15" value="{$aro_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="axo_section_value" size="15" value="{$axo_section_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="axo_value" size="15" value="{$axo_value}"></td>
    <td><input class="form-control form-control-sm" type="text" name="root_aro_group_id" size="15" value="{$root_aro_group_id}"></td>
    <td><input class="form-control form-control-sm" type="text" name="root_axo_group_id" size="15" value="{$root_axo_group_id}"></td>
    <td><strong>)</strong></td>
  </tr>
  <tr class="table-secondary text-center">
    <td colspan="10">
      <input type="submit" class="btn btn-sm btn-primary" name="action" value="Submit">
    </td>
  </tr>
</table>
{if $acls|@count gt 0}
<br>
<table class="table table-sm table-bordered">
  <tr class="thead-dark">
    <th rowspan="2" width="4%">ACL ID</th>
    <th colspan="2">ACO</th>
    <th colspan="2">ARO</th>
    <th colspan="2">AXO</th>
    <th colspan="2">ACL</th>
  </tr>
  <tr class="thead-dark">
    <th width="12%">Section</th>
    <th width="12%">Value</th>
    <th width="12%">Section</th>
    <th width="12%">Value</th>
    <th width="12%">Section</th>
    <th width="12%">Value</th>
    <th width="8%">Access</th>
    <th width="16%">Updated Date</th>
  </tr>
{foreach from=$acls item=acl}
  <tr class="align-top text-left">
    <td class="align-middle text-center" rowspan="2">
        {$acl.id}
    </td>
    <td class="text-nowrap">
    {$acl.aco_section_value}
    </td>
    <td class="text-nowrap">
    {$acl.aco_value}
    </td>

    <td class="text-nowrap">
    {$acl.aro_section_value}<br>
    </td>
    <td class="text-nowrap">
    {$acl.aro_value}<br>
    </td>

    <td class="text-nowrap">
    {$acl.axo_section_value}<br>
    </td>
    <td class="text-nowrap">
    {$acl.axo_value}<br>
    </td>

    <td class="align-middle text-center {if $acl.allow}green{else}red{/if}">
    {if $acl.allow}
      ALLOW
    {else}
      DENY
    {/if}
    </td>
    <td class="align-middle text-center">
        {$acl.updated_date}
     </td>
  </tr>
  <tr class="align-middle text-left">
    <td colspan="4">
        <strong>Return Value:</strong> {$acl.return_value}<br>
    </td>
    <td colspan="4">
        <strong>Note:</strong> {$acl.note}
    </td>
  </tr>
{/foreach}
</table>
{/if}
<input type="hidden" name="return_page" value="{$return_page}">
</form>
{include file="phpgacl/footer.tpl"}
