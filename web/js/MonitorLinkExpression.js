//
// ZoneMinder MonitorLink Expression
// Copyright (C) 2022 ZoneMinder Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

function tokenize(expr) {
  const tokens = [];

  let first_index = 0;
  let second_index = 0;
  while (second_index < expr.length) {
    const character = expr.at(second_index);

    if (character == '&' || character == '|' || character == ',') {
      if (first_index != second_index) {
        tokens[tokens.length] = {type: 'link', value: expr.substring(first_index, second_index)};
      }
      tokens[tokens.length] = {type: character, value: character};
      first_index = second_index+1;
    } else if (character == '(' || character == ')') {
      if (first_index != second_index) {
        tokens[tokens.length] = {type: 'link', value: expr.substring(first_index, second_index)};
      }
      // Now check for repeats
      let third = second_index+1;

      for (; third<expr.length; third++) {
        if (expr.at(i) != character) break;
      }
      if (third != second_index+1) {
        tokens[tokens.length] = {type: character, value: expr.substring(second_index, third)};
      } else {
        tokens[tokens.length] = {type: character, value: character};
      }
      first_index = third;
    }
    second_index++;
  } // end for second_index

  if (second_index) {
    if (second_index != first_index) {
      tokens[tokens.length] = {type: 'link', value: expr.substring(first_index, second_index)};
    }
  }
  return tokens;
}

function count_terms(tokens) {
  let term_count = 0;
  for (let i=0; i<tokens.length; i++) {
    if (tokens[i].type == 'link') term_count++;
  }
  return term_count;
}

function expr_to_ui(expr, container) {
  container.html('');
  const tokens = tokenize(expr);
  //const term_count = count_terms(tokens);
  let brackets = 0;
  const used_monitorlinks = [];

  // Every monitorlink should have possible parenthesis on either side of it
  if (tokens.length > 3) {
    if (tokens[0].type != '(') {
      tokens.unshift({type: '(', value: ''});
    }
    if (tokens[tokens.length-1].type != ')') {
      tokens.push({type: ')', value: ''});
    }
    for (let token_index = 1; token_index < tokens.length-1; token_index++) {
      const token = tokens[token_index];

      if (token.type == 'link') {
        if (tokens[token_index-1].type != '(' && tokens[token_index-1].type != ')') {
          console.log("Adding () before ", token, token_index);
          tokens.splice(token_index, 0, {type: '()', value: ''});
          token_index ++;
        }
        if (tokens[token_index+1].type != '(' && tokens[token_index+1].type != ')') {
          console.log("Adding () after ", token, token_index);
          tokens.splice(token_index+1, 0, {type: '()', value: ''});
          token_index ++;
        }
        brackets++;
        used_monitorlinks.push(token.value);
      }
    } // end foreach token
  }
  brackets --;

  for (let token_index = 0; token_index < tokens.length; token_index++) {
    const token = tokens[token_index];

    if (token.type == 'link') {
      const select = $j('<select></select>');
      for ( monitor_id in monitors ) {
        const monitor = monitors[monitor_id];
        if (mid && (monitor.Id == mid)) continue;
        select.append('<option value="' + monitor.Id + '">' + monitor.Name + ' : All Zones</option>');
        for ( zone_id in zones ) {
          const zone = zones[zone_id];
          if ( monitor.Id == zone.MonitorId ) {
            select.append('<option value="' + monitor.Id+':'+zone.Id + '">' + monitor.Name + ' : ' +zone.Name + '</option>');
          }
        } // end foreach zone
      } // end foreach monitor
      select.val(token.value);
      select.on('change', update_expr);
      token.html = select;
    } else if (token.type == '(' || token.type == ')') {
      const select = $j('<select></select>');
      select.append('<option value="0"></option>');
      for (var i = 1; i <= brackets; i++) { // build bracket options
        select.append('<option value="' + token.type.repeat(i) + '">' + token.type.repeat(i) + '</option>');
      }
      select.val(token.value);
      select.on('change', update_expr);
      token.html = select;
    } else if (token.type == '()') {
      const select = $j('<select></select>');
      select.append('<option value=""></option>');
      for (let i = 1; i <= brackets; i++) {
        select.append('<option value="' + String('(').repeat(i) + '">' + String('(').repeat(i) + '</option>');
      }
      for (let i = 1; i <= brackets; i++) { // build bracket options
        select.append('<option value="' + String(")").repeat(i) + '">' + String(")").repeat(i) + '</option>');
      }
      select.val(token.value);
      select.on('change', update_expr);
      token.html = select;
    } else {
      const select = $j('<select></select>');
      select.append('<option value="|">or</option>');
      select.append('<option value="&">and</option>');
      select.val(token.type);
      select.on('change', update_expr);
      token.html = select;
    }
    container.append(token.html);
  } // end foreach token
  container.append('<br/>');
  const select = $j('<select id="monitorLinks"></select>');
  select.append('<option value="">Add MonitorLink</option>');
  for (monitor_id in monitors) {
    const monitor = monitors[monitor_id];
    if (mid && (monitor.Id == mid)) continue;
    //if (!array_search(monitor.Id, used_monitorlinks))
    select.append('<option value="' + monitor.Id + '">' + monitor.Name + ' : All Zones</option>');
    for ( zone_id in zones ) {
      const zone = zones[zone_id];
      if ( monitor.Id == zone.MonitorId ) {
        if (!array_search(monitor.Id+':'+zone.Id, used_monitorlinks)) {
          select.append('<option value="' + monitor.Id+':'+zone.Id + '">' + monitor.Name + ' : ' +zone.Name + '</option>');
        }
      }
    } // end foreach zone
  } // end foreach monitor
  select.on('change', add_to_expr);
  container.append(select);
} // end expr_to_ui

function array_search(needle, haystack) {
  for (index in haystack) {
    if (haystack[index] == needle) return true;
  }
  return false;
}

function add_to_expr() {
  const expr = $j('[name="newMonitor[LinkedMonitors]"]');
  const oldval = expr.val();
  expr.val(oldval == '' ? $j('#monitorLinks').val() : oldval + '|' + $j('#monitorLinks').val());
  expr_to_ui(expr.val(), $j('#LinkedMonitorsUI'));
}

function update_expr(ev) {
  ui_to_expr($j('#LinkedMonitorsUI'), $j('[name="newMonitor[LinkedMonitors]"]'));
}

function ui_to_expr(container, expr_input) {
  let expr = '';
  const children = container.children();
  for (let i = 0; i < children.length; i++) {
    expr += $j(children[i]).val();
  }
  expr_input.val(expr);
}

function parse_expression(tokens) {
  if (tokens.length == 1) {
    return {token: tokens[0]};
  }

  let left = parse_and(tokens);
  if (token_index < tokens.length && tokens[token_index] != '|' && tokens[token_index] != ',') {
    return left;
  }

  while (token_index < tokens.length && ( tokens[token_index] == '|' || tokens[token_index] == ',')) {
    var logical_or = {type: '|'};
    token_index++;

    var right = parse_and(tokens);
    if (right == null) {
      return null;
    }

    logical_or.left = left;
    logical_or.right = right;
    left = logical_or;
  }

  return left;
} // end function parse_expression

function parse_and(tokens) {
  var left = parse_parentheses(tokens);

  if (left == null) {
    return null;
  }

  while ((token_index < tokens.length) && (tokens[token_index] == '&')) {
    ++token_index;

    var logical_and = {type: '&'};

    right = parse_parentheses(tokens);
    if (right == null) {
      return null;
    }

    logical_and.left = left;
    logical_and.right = right;
    left = logical_and;
  }

  return left;
}


function parse_parentheses(tokens) {
  if (token_index == tokens.length) {
    return null;
  }

  if (tokens[token_index] == '(') {
    ++token_index;
    var expression = parse_expression(tokens);

    // Because we are parsing a left, there SHOULD be a remaining right. If not, invalid.
    if (token_index == tokens.length) return null;

    if (tokens[token_index++] == ')') {
      return expression;
    }
  } else if (tokens[token_index].type == MONITORLINK) {
    var link = {token: tokens[token_index]};
    token_index++;
    return link;
  }

  return null;
}
