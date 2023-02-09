{include file="phpgacl/header.tpl"}
<script>
{$js_array}
</script>
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
<body onload="populate(document.acl_admin.aco_section,document.acl_admin.elements['aco[]'], '{$js_aco_array_name}');populate(document.acl_admin.aro_section,document.acl_admin.elements['aro[]'], '{$js_aro_array_name}')">
{include file="phpgacl/navigation.tpl"}
  <form method="post" name="acl_admin" action="acl_admin.php" onsubmit="select_all(document.acl_admin.elements['selected_aco[]']);select_all(document.acl_admin.elements['selected_aro[]']);select_all(document.acl_admin.elements['selected_aro[]']);return true;">

      <table class="table table-bordered table-sm text-center">
        <tbody>
          <tr class="thead-dark">
            <th width="24%">Sections</th>
            <th width="24%">Access Control Objects</th>
            <th width="4%">&nbsp;</th>
            <th width="24%">Selected</th>
            <th width="24%">Access</th>
          </tr>
          <tr class="text-center align-top">
            <td>
              <a class="btn btn-sm btn-warning" href="edit_object_sections.php?object_type=aco&return_page={$return_page}" title="Edit">
                <i class="{#icon_edit#}"></i> Edit
              </a>
              <select class="form-control form-control-sm mt-1" name="aco_section" tabindex="0" size="10" onclick="populate(document.acl_admin.aco_section,document.acl_admin.elements['aco[]'], '{$js_aco_array_name}')" style="height:15rem;">
                {html_options options=$options_aco_sections selected=$aco_section_value}
              </select>
            </td>
            <td>
              <a class="btn btn-sm btn-warning" href="javascript: location.href = 'edit_objects.php?object_type=aco&section_value=' + document.acl_admin.aco_section.options[document.acl_admin.aco_section.selectedIndex].value + '&return_page={$return_page}';" title="Edit">
                <i class="{#icon_edit#}"></i> Edit
              </a>
              <select class="form-control form-control-sm mt-1" name="aco[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
              </select>
            </td>
            <td class="align-middle">
              <div class="btn-group-vertical">
                <button class="btn btn-sm btn-primary" type="button" name="select" onClick="select_item(document.acl_admin.aco_section, document.acl_admin.elements['aco[]'], document.acl_admin.elements['selected_aco[]'])">
                  <i class="{#icon_right#} pl-2 pr-2"></i>
                </button>
                <button class="btn btn-sm btn-primary" type="button" name="deselect" onClick="deselect_item(document.acl_admin.elements['selected_aco[]'])">
                  <i class="{#icon_left#} pl-2 pr-2"></i>
                </button>
              </div>
            </td>
            <td class="align-bottom">
              <select class="form-control form-control-sm" name="selected_aco[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
        {html_options options=$options_selected_aco selected=$selected_aco}
              </select>
            </td>
            <td class="align-middle">
              <table>
                <tr class="text-left">
                  <td class="border-0 bg-success"><input type="radio" class="radio" name="allow" value="1" {if $allow==1}checked{/if}></td>
                  <td class="border-0 bg-success">Allow</td>
                </tr>
                <tr class="text-left">
                  <td class="border-0 bg-danger"><input type="radio" class="radio" name="allow" value="0" {if $allow==0}checked{/if}></td>
                  <td class="border-0 bg-danger text-white">Deny</td>
                </tr>
                <tr class="spacer">
                  <td class="border-0" colspan="2"></td>
                </tr>
                <tr class="text-left">
                  <td class="border-0"><input type="checkbox" class="checkbox" name="enabled" value="1" {if $enabled==1}checked="checked"{/if}></td>
                  <td class="border-0">Enabled</td>
                </tr>
             </table>
           </td>
          </tr>

          <tr class="thead-dark">
            <th>Sections</th>
            <th>Access Request Objects</th>
            <th>&nbsp;</th>
            <th>Selected</th>
            <th>Groups</th>
          </tr>
          <tr class="align-top text-center">
            <td>
              <a class="btn btn-sm btn-warning" href="edit_object_sections.php?object_type=aro&return_page={$return_page}" title="Edit">
                <i class="{#icon_edit#}"></i> Edit
              </a>
              <select class="form-control form-control-sm mt-1" name="aro_section" tabindex="0" size="10" onclick="populate(document.acl_admin.aro_section,document.acl_admin.elements['aro[]'],'{$js_aro_array_name}')">
                {html_options options=$options_aro_sections selected=$aro_section_value}
              </select>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-sm btn-warning" href="javascript: location.href = 'edit_objects.php?object_type=aro&section_value=' + document.acl_admin.aro_section.options[document.acl_admin.aro_section.selectedIndex].value + '&return_page={$return_page}';" title="Edit">
                  <i class="{#icon_edit#}"></i> Edit
                </a>
                <a class="btn btn-sm btn-info" href="#" onClick="window.open('object_search.php?src_form=acl_admin&object_type=aro&section_value=' + document.acl_admin.aro_section.options[document.acl_admin.aro_section.selectedIndex].value + '&return_page={$return_page}','','status=yes,width=400,height=400');return false;" title="Search">
                  <i class="{#icon_search#}"></i> Search
                </a>
              </div>
              <select class="form-control form-control-sm mt-1" name="aro[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
              </select>
            </td>
            <td class="align-middle">
              <div class="btn-group-vertical">
                <button type="button" class="btn btn-sm btn-primary" name="select" onClick="select_item(document.acl_admin.aro_section, document.acl_admin.elements['aro[]'], document.acl_admin.elements['selected_aro[]'])"><i class="{#icon_right#} pl-2 pr-2"></i></button>
                <button type="button" class="btn btn-sm btn-primary" name="deselect" onClick="deselect_item(document.acl_admin.elements['selected_aro[]'])"><i class="{#icon_left#} pl-2 pr-2"></i></button>
              </div>
            </td>
            <td class="align-bottom">
             <select class="form-control form-control-sm" name="selected_aro[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
         {html_options options=$options_selected_aro selected=$selected_aro}
             </select>
            </td>
            <td>
              <a class="btn btn-sm btn-warning" href="group_admin.php?group_type=aro&return_page={$SCRIPT_NAME}?action={$action}&acl_id={$acl_id}" title="Edit">
                <i class="{#icon_edit#}"></i> Edit
              </a>
        <select class="form-control form-control-sm mt-1" name="aro_groups[]" tabindex="0" size="8" multiple="multiple">
          {html_options options=$options_aro_groups selected=$selected_aro_groups}
        </select>
        <button type="button" class="btn btn-sm btn-dark mt-1" name="Un-Select" onClick="unselect_all(document.acl_admin.elements['aro_groups[]'])">
          <i class="{#icon_deselect#}"></i> Un-Select
        </button>
            </td>
          </tr>

          <tr>
            <th colspan="5">
              <div class="btn-group">
                <a class="btn btn-sm btn-primary" href="javascript: showObject('axo_row1');showObject('axo_row2');setCookie('show_axo',1);" title="Show">
                  <i class="{#icon_show#}"></i> Show
                </a>
                <a class="btn btn-sm btn-secondary" href="javascript: hideObject('axo_row1');hideObject('axo_row2');deleteCookie('show_axo');" title="Hide">
                  <i class="{#icon_hide#}"></i> Hide
                </a>
              </div>
              Access eXtension Objects (Optional)
            </th>
          </tr>

          <tr class="thead-dark{if !$show_axo} d-none{/if}" id="axo_row1">
            <th class="thead-dark">Sections</th>
            <th class="thead-dark">Access eXtension Objects</th>
            <th class="thead-dark">&nbsp;</th>
            <th class="thead-dark">Selected</th>
            <th class="thead-dark">Groups</th>
          </tr>
          <tr class="align-top text-center{if !$show_axo} d-none{/if}" id="axo_row2">
            <td>
              <a class="btn btn-sm btn-warning" href="edit_object_sections.php?object_type=axo&return_page={$return_page}">
                <i class="{#icon_edit#}"></i> Edit
              </a>
              <select class="form-control form-control-sm mt-1" name="axo_section" tabindex="0" size="10" onclick="populate(document.acl_admin.axo_section,document.acl_admin.elements['axo[]'],'{$js_axo_array_name}')" style="height:15rem;">
                {html_options options=$options_axo_sections selected=$axo_section_value}
              </select>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-sm btn-warning" href="javascript: location.href = 'edit_objects.php?object_type=axo&section_value=' + document.acl_admin.axo_section.options[document.acl_admin.axo_section.selectedIndex].value + '&return_page={$return_page}';" title="Edit">
                  <i class="{#icon_edit#}"></i> Edit
                </a>
                <a class="btn btn-sm btn-info" href="#" onClick="window.open('object_search.php?src_form=acl_admin&object_type=axo&section_value=' + document.acl_admin.axo_section.options[document.acl_admin.axo_section.selectedIndex].value + '&return_page={$return_page}','','status=yes,width=400,height=400');return false;" title="Search">
                  <i class="{#icon_search#}"></i> Search
                </a>
              </div>
              <select class="form-control form-control-sm mt-1" name="axo[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
              </select>
            </td>
            <td class="align-middle">
              <div class="btn-group-vertical">
                <button type="button" class="btn btn-sm btn-primary" name="select" onClick="select_item(document.acl_admin.axo_section, document.acl_admin.elements['axo[]'], document.acl_admin.elements['selected_axo[]'])"><i class="{#icon_right#} pl-2 pr-2"></i></button>
                <button type="button" class="btn btn-sm btn-primary" name="deselect" onClick="deselect_item(document.acl_admin.elements['selected_axo[]'])"><i class="{#icon_left#} pl-2 pr-2"></i></button>
              </div>
            </td>
            <td class="align-bottom">
              <select class="form-control form-control-sm" name="selected_axo[]" tabindex="0" size="10" multiple="multiple" style="height:15rem;">
                {html_options options=$options_selected_axo selected=$selected_axo}
              </select>
            </td>
            <td>
              <a class="btn btn-sm btn-warning" href="group_admin.php?group_type=axo&return_page={$SCRIPT_NAME}?action={$action}&acl_id={$acl_id}">
                <i class="{#icon_edit#}"></i> Edit
              </a>
              <select class="form-control form-control-sm mt-1" name="axo_groups[]" tabindex="0" size="8" multiple="multiple">
                {html_options options=$options_axo_groups selected=$selected_axo_groups}
              </select>
              <button type="button" class="btn btn-sm btn-dark mt-1" name="Un-Select" onClick="unselect_all(document.acl_admin.elements['axo_groups[]'])">
                <i class="{#icon_deselect#}"></i> Un-Select
              </button>
            </td>
        </tr>

        <tr class="thead-dark">
      <th colspan="5">Miscellaneous Attributes</th>
    </tr>
        <tr class="align-top text-left">
      <td class="text-center">
                <strong>ACL Section</strong>
            </td>
      <td>
                <label for="return_value" class="font-weight-bold">Extended Return Value:</label>
            </td>
            <td colspan="4">
                <input class="form-control form-control-sm" type="text" name="return_value" size="50" value="{$return_value}" id="return_value">
            </td>
    </tr>
    <tr class="align-top text-left">
      <td class="text-center">
      <a class="btn btn-sm btn-warning" href="edit_object_sections.php?object_type=acl&return_page={$return_page}" title="Edit">
        <i class="{#icon_edit#}"></i> Edit
      </a>
      <select class="form-control form-control-sm mt-1" name="acl_section" tabindex="0" size="3">
        {html_options options=$options_acl_sections selected=$acl_section_value}
      </select>
      </td>
          <td><label for="note" class="font-weight-bold">Note:</label></td>
          <td colspan="4"><textarea class="form-control form-control-sm" name="note" id="note" rows="4" cols="40">{$note}</textarea></td>
    </tr>
        <tr class="table-secondary text-center">
          <td colspan="5">
            <div class="btn-group">
              <button type="submit" class="btn btn-sm btn-primary" name="action" value="Submit"><i class="{#icon_submit#}"></i> Submit</button>
              <button type="reset" class="btn btn-sm btn-dark" value="Reset"><i class="{#icon_reset#}"></i> Reset</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  <input type="hidden" name="acl_id" value="{$acl_id}">
  <input type="hidden" name="return_page" value="{$return_page}">
</form>
{include file="phpgacl/footer.tpl"}
