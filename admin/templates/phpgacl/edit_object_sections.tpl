{include file="phpgacl/header.tpl"}
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
  <body>
    {include file="phpgacl/navigation.tpl"}
    <form method="post" name="edit_object_sections" action="edit_object_sections.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="pager">
            <td colspan="11">
                {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?object_type=$object_type&"}
            </td>
          </tr>
          <tr class="thead-dark">
            <th width="2%">ID</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th width="4%">Functions</th>
            <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
          </tr>
{section name=x loop=$sections}
          <tr class="align-top text-center">
            <td>
              {$sections[x].id}
              <input type="hidden" name="sections[{$sections[x].id}][]" value="{$sections[x].id}">
            </td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="sections[{$sections[x].id}][]" value="{$sections[x].value}"></td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="sections[{$sections[x].id}][]" value="{$sections[x].order}"></td>
            <td><input class="form-control form-control-sm" type="text" size="40" name="sections[{$sections[x].id}][]" value="{$sections[x].name}"></td>
            <td>&nbsp;</td>
            <td><input type="checkbox" class="checkbox" name="delete_sections[]" value="{$sections[x].id}"></td>
          </tr>
{/section}
          <tr class="pager">
            <td colspan="6">
                {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?object_type=$object_type&"}
            </td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>
          <tr class="text-center">
            <td colspan="6"><strong>Add {$object_type|upper} Sections</strong></td>
          </tr>
          <tr class="thead-dark">
            <th>ID</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th>Functions</th>
            <th>&nbsp;</td>
          </tr>
{section name=y loop=$new_sections}
          <tr class="align-top text-center">
            <td>N/A</td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="new_sections[{$new_sections[y].id}][]" value=""></td>
            <td><input class="form-control form-control-sm" type="text" size="10" name="new_sections[{$new_sections[y].id}][]" value=""></td>
            <td><input class="form-control form-control-sm" type="text" size="40" name="new_sections[{$new_sections[y].id}][]" value=""></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
{/section}
          <tr class="table-secondary text-center">
            <td colspan="4">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-primary" name="action" value="Submit"><i class="{#icon_submit#}"></i> Submit</button>
                <button type="reset" class="btn btn-sm btn-dark" value=""><i class="{#icon_reset#}"></i> Reset</button>
              </div>
            </td>
            <td colspan="2">
              <button type="submit" class="btn btn-sm btn-danger" name="action" value="Delete"><i class="{#icon_delete#}"></i> Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    <input type="hidden" name="object_type" value="{$object_type}">
    <input type="hidden" name="return_page" value="{$return_page}">
    </form>
{include file="phpgacl/footer.tpl"}
