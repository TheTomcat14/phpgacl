{include file="phpgacl/header.tpl"}
<script LANGUAGE="JavaScript">
{$js_array}
</script>
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
  <body onload="populate(document.assign_group.{$group_type}_section,document.assign_group.elements['objects[]'], '{$js_array_name}')">
    {include file="phpgacl/navigation.tpl"}
    <form method="post" name="assign_group" action="assign_group.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="thead-dark">
            <th width="32%">Sections</th>
            <th width="32%">{$object_type}s</th>
            <th width="4%">&nbsp;</th>
            <th width="32%">Selected</th>
          </tr>
          <tr class="align-top text-center">
            <td>
              <a class="btn btn-sm btn-warning" href="edit_object_sections.php?object_type={$group_type}&return_page={$return_page}" title="Edit">
                <i class="fa fa-pencil"></i> Edit
              </a>
              <br>
              <select class="form-control form-control-sm mt-1" name="{$group_type}_section" tabindex="0" size="10" width="200" onclick="populate(document.assign_group.{$group_type}_section,document.assign_group.elements['objects[]'],'{$js_array_name}')">
                {html_options options=$options_sections selected=$section_value}
              </select>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-sm btn-warning" href="javascript: location.href = 'edit_objects.php?object_type={$group_type}&section_value=' + document.assign_group.{$group_type}_section.options[document.assign_group.{$group_type}_section.selectedIndex].value + '&return_page={$return_page}';" class="Edit">
                <i class="{#edit_icon#}"></i> Edit
                </a>
                <a class="btn btn-sm btn-info" href="#" onClick="window.open('object_search.php?src_form=assign_group&object_type={$group_type}&section_value=' + document.assign_group.{$group_type}_section.options[document.assign_group.{$group_type}_section.selectedIndex].value,'','status=yes,width=400,height=400','','status=yes,width=400,height=400');" title="Search">
                  <i class="fa fa-search"></i> Search
                </a>
              </div>
              <select class="form-control form-control-sm mt-1" name="objects[]" tabindex="0" size="10" width="200" multiple="multiple">
              </select>
            </td>
            <td class="align-middle">
              <div class="btn-group-vertical">
                <button type="button" class="btn btn-sm btn-primary" name="select" onClick="select_item(document.assign_group.{$group_type}_section, document.assign_group.elements['objects[]'], document.assign_group.elements['selected_{$group_type}[]'])"><i class="{#icon_right#} pl-2 pr-2"></i></button>
                <button type="button" class="btn btn-sm btn-primary" name="deselect" onClick="deselect_item(document.assign_group.elements['selected_{$group_type}[]'])"><i class="{#icon_left#} pl-2 pr-2"></i></button>
              </div>
            </td>
            <td>
              <select class="form-control form-control-sm mt-1" name="selected_{$group_type}[]" tabindex="0" size="10" width="200" multiple="multiple">
        {html_options options=$options_selected_objects selected=$selected_object}
              </select>
            </td>
          </tr>
          <tr class="table-secondary text-center">
            <td colspan="4">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-primary" name="action" value="Submit" title="Submit"><i class="{#icon_submit#}"></i> Submit</button>
                <button type="reset" class="btn btn-sm btn-dark" value="Reset" title="Reset"><i class="{#icon_reset#}"></i> Reset</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <br>
      <table class="table table-sm table-bordered">
        <tr class="text-center">
          <td colspan="5">
            <strong>{$total_objects}</strong> {$group_type|upper}s in Group: <strong>{$group_name}</strong>
          </td>
        </tr>
        <tr class="pager">
          <td colspan="5">
            {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?group_type=$group_type&group_id=$group_id&"}
          </td>
        </tr>
        <tr class="thead-dark">
          <th>Section</th>
          <th>{$object_type}</th>
          <th>{$group_type|upper} Value</th>
          <th width="4%">Functions</th>
          <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
        </tr>
        {foreach from=$rows item=row}
        <tr class="align-top text-center">
          <td>
            {$row.section}
          </td>
          <td>
            {$row.name}
          </td>
          <td>
            {$row.value}
          </td>
          <td>
            <a class="btn btn-sm btn-info" href="acl_list.php?action=Filter&filter_{$group_type}_section={$row.section_value}&filter_{$group_type}={$row.name}&return_page={$return_page}" title="ACLs">
              <i class="{#icon_list#}"></i> ACLs
            </a>
          </td>
          <td>
            <input type="checkbox" class="checkbox" name="delete_assigned_object[]" value="{$row.section_value}^{$row.value}">
          </td>
        </tr>
        {/foreach}
        <tr class="pager">
          <td colspan="5">
            {include file="phpgacl/pager.tpl" pager_data=$paging_data link="?"}
          </td>
        </tr>
        <tr class="table-secondary text-center">
          <td colspan="3">&nbsp;</td>
          <td colspan="2">
            <button type="submit" class="btn btn-sm btn-danger" name="action" value="Remove"><i class="{#icon_delete#}"></i> Remove</button>
          </td>
        </tr>
      </table>
      <input type="hidden" name="group_id" value="{$group_id}">
      <input type="hidden" name="group_type" value="{$group_type}">
      <input type="hidden" name="return_page" value="{$return_page}">
    </form>
{include file="phpgacl/footer.tpl"}
