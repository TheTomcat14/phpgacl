{include file="phpgacl/header.tpl"}
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
  <body>
    {include file="phpgacl/navigation.tpl"}
    <form method="post" name="edit_objects" action="edit_objects.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="pager">
            <td colspan="7">
                {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?section_value=$section_value&object_type=$object_type&"}
            </td>
          </tr>
          <tr class="thead-dark">
            <th width="2%">ID</th>
            <th>Section</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th width="4%">Functions</th>
            <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
          </tr>
{section name=x loop=$objects}
          <tr class="align-top text-center">
            <td>
              {$objects[x].id}
              <input type="hidden" name="objects[{$objects[x].id}][]" value="{$objects[x].id}">
            </td>
            <td>{$section_name}</td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="objects[{$objects[x].id}][]" value="{$objects[x].value}"></td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="objects[{$objects[x].id}][]" value="{$objects[x].order}"></td>
            <td><input class="form-control form-control-sm" type="text" size="40" name="objects[{$objects[x].id}][]" value="{$objects[x].name}"></td>
            <td>&nbsp;</td>
            <td><input type="checkbox" class="checkbox" name="delete_object[]" value="{$objects[x].id}"></td>
          </tr>
{/section}
          <tr class="pager">
            <td colspan="7">
                {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?section_value=$section_value&object_type=$object_type&"}
            </td>
          </tr>
          <tr class="pt-2">
            <td colspan="7"></td>
          </tr>
          <tr class="text-center">
            <td colspan="7"><strong>Add {$object_type|upper}s</strong></td>
          </tr>
          <tr class="thead-dark">
            <th>ID</th>
            <th>Section</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th>Functions</th>
            <th>&nbsp;</th>
          </tr>
{section name=y loop=$new_objects}
          <tr class="align-top text-center">
            <td>N/A</td>
            <td>{$section_name}</td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="new_objects[{$new_objects[y].id}][]" value=""></td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="new_objects[{$new_objects[y].id}][]" value=""></td>
            <td><input class="form-control form-control-sm" type="text" size="40" name="new_objects[{$new_objects[y].id}][]" value=""></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
{/section}
          <tr class="table-secondary text-center">
            <td colspan="5">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-primary" name="action" value="Submit"><i class="{#icon_submit#}"></i> Submit</button>
                <button type="reset" class="btn btn-sm btn-dark" value="Reset"><i class="{#icon_reset#}"></i> Reset</button>
              </div>
            </td>
            <td colspan="2">
              <button type="submit" class="btn btn-sm btn-danger" name="action" value="Delete"><i class="{#icon_delete#}"></i> Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    <input type="hidden" name="section_value" value="{$section_value}">
    <input type="hidden" name="object_type" value="{$object_type}">
    <input type="hidden" name="return_page" value="{$return_page}">
  </form>
{include file="phpgacl/footer.tpl"}
