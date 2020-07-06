{include file="phpgacl/header.tpl"}
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
  <body>
    {include file="phpgacl/navigation.tpl"}
    <form method="post" name="edit_group" action="edit_group.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="thead-dark">
            <th width="2%">ID</th>
            <th width="40%">Name</th>
            <th width="20%">Value</th>
            <th width="4%">Objects</th>
            <th width="32%">Functions</th>
            <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
          </tr>
{foreach from=$groups item=group}
          <tr class="align-middle text-center">
            <td>{$group.id}</td>
            <td class="text-left">{$group.name}</td>
            <td class="text-left">{$group.value}</td>
            <td>{$group.object_count}</td>
            <td>
              <div class="btn-group">
                <a class="btn btn-sm btn-primary" href="assign_group.php?group_type={$group_type}&group_id={$group.id}&return_page={$return_page}" title="Assign {$group_type|upper}">
                  <i class="{#icon_assign#}"></i> Assign {$group_type|upper}
                </a>
                <a class="btn btn-sm btn-success" href="edit_group.php?group_type={$group_type}&parent_id={$group.id}&return_page={$return_page}" title="Add Child">
                  <i class="{#icon_add#}"></i> Add Child
                </a>
                <a class="btn btn-sm btn-warning" href="edit_group.php?group_type={$group_type}&group_id={$group.id}&return_page={$return_page}" title="Edit">
                  <i class="{#icon_edit#}"></i> Edit
                </a>
                <a class="btn btn-sm btn-info" href="acl_list.php?action=Filter&filter_{$group_type}_group={$group.raw_name|urlencode}&return_page={$return_page}" title="ACLs">
                  <i class="{#icon_list#}"></i> ACLs
                </a>
              </div>
            </td>
            <td><input type="checkbox" class="checkbox" name="delete_group[]" value="{$group.id}"></td>
          </tr>
{/foreach}
          <tr class="table-secondary text-center">
            <td colspan="4">&nbsp;</td>
            <td colspan="2" class="text-nowrap">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-success" name="action" value="Add" title="Add"><i class="{#icon_add#}"></i> Add</button>
                <button type="submit" class="btn btn-sm btn-danger" name="action" value="Delete" title="Delete"><i class="{#icon_delete#}"></i> Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    <input type="hidden" name="group_type" value="{$group_type}">
    <input type="hidden" name="return_page" value="{$return_page}">
  </form>
{include file="phpgacl/footer.tpl"}
