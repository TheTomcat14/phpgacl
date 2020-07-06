{include file="phpgacl/header.tpl"}
  </head>
<body>
{include file="phpgacl/navigation.tpl"}
<form method="post" name="acl_list" action="acl_list.php">
<table class="table table-sm table-bordered">
  <tr class="pager">
  <td colspan="11">
    {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?"}
  </td>
  </tr>
  <tr class="thead-dark">
    <th>#</th>
    <th>Section > ACO</th>
    <th>Section > ARO</th>
    <th>Return Value</th>
    <th>ACL_CHECK() Code</th>
    <th>Debug</th>
    <th>Time (ms)</th>
    <th>Access</th>
  </tr>
  {section name=x loop=$acls}
  <tr>
    <td class="align-middle text-center">
    {$smarty.section.x.iteration}
    </td>
    <td class="align-middle text-center">
    {$acls[x].display_aco_name}
    </td>
    <td class="align-top text-left">
        {$acls[x].aro_section_name} > {$acls[x].aro_name}
    </td>
    <td class="align-top text-center">
        {$acls[x].return_value}<br>
     </td>
    <td class="align-top text-left">
    acl_check('{$acls[x].aco_section_value}', '{$acls[x].aco_value}', '{$acls[x].aro_section_value}', '{$acls[x].aro_value}')
    </td>
    <td class="align-top text-center text-nowrap">
     <a class="btn btn-sm btn-primary" href="acl_debug.php?aco_section_value={$acls[x].aco_section_value}&aco_value={$acls[x].aco_value}&aro_section_value={$acls[x].aro_section_value}&aro_value={$acls[x].aro_value}&action=Submit">
       <i class="fa fa-bug"></i> debug
     </a>
    </td>
    <td class="align-top text-center">
    {$acls[x].acl_check_time}
    </td>
    <td class="align-middle text-center {if $acls[x].access}table-success{else}table-danger{/if}">
    {if $acls[x].access}
      ALLOW
    {else}
      DENY
    {/if}
    </td>
  </tr>
  {/section}
  <tr classs="pager">
  <td colspan="11">
    {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?"}
  </td>
  </tr>
</table>
</form>

<table class="table table-sm table-borderd">
  <tr>
  <th colspan="2">
    Summary
  </th>
  </tr>
  <tr class="text-center">
  <td>
    <strong>Total ACL Check(s)</strong>
  </td>
  <td>
    {$total_acl_checks}
  </td>
  </tr>
  <tr class="text-center">
  <td>
    <strong>Average Time / Check</strong>
  </td>
  <td>
    {$avg_acl_check_time}ms
  </td>
  </tr>
</table>
<br>
<table class="table table-sm table-bordered">
  <th>
    Do you want to test 3-dimensional ACLs?
  </th>
  <tr class="text-center">
    <td>
      <a class="btn btn-sm btn-primary" href="acl_test3.php">3-dimensional ACLs</a>
    </td>
  </tr>
</table>
<br>
{include file="phpgacl/footer.tpl"}
