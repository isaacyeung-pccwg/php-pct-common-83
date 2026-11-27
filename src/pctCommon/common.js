function openWindowWithPost(url, name, keys, values) {
  var newWindow = window.open(url, name);
  if (!newWindow) return false;
  var html = "";
  html +=
    "<html><head></head><body><form id='formid' method='post' action='" +
    url +
    "'>";
  if (keys && values && keys.length == values.length)
    for (var i = 0; i < keys.length; i++)
      html +=
        "<input type='hidden' name='" +
        keys[i] +
        "' value='" +
        values[i] +
        "'/>";
  html +=
    "</form><script type='text/javascript'>document.getElementById(\"formid\").submit()</script></body></html>";
  newWindow.document.write(html);
  return newWindow;
}

function InnerHtmlSetter(o, v) {
  o.innerHTML = v;
}

function getObject(o, n, target, form) {
  if (!target) target = "this";
  if (!form) form = "Input";

  return n == null
    ? eval(target + ".document." + form + ".elements['" + o + "']")
    : eval(target + ".document." + form + ".elements['" + o + "[" + n + "]']");
}

function RadioChecker(o, v) {
  for (i = 0; i < o.length; i++) {
    if (viewmode) o[i].disabled = true;
    if (o[i].value == v) o[i].checked = "checked";
  }
}

function InputSetter(o, v) {
  o.value = v;
}

function OptionSelector(o, v, last) {
  var flag = false;
  for (i = 0; i < o.length; i++)
    if (o[i].value == v && o[i].value != "") {
      flag = true;
      //alert("FIND " + o[i].value + " " + v);
      o.selectedIndex = i;
    }
  if (!flag && last) o.selectedIndex = i - 1;
  else if (!flag) o.selectedIndex = 0;
  //alert(flag + '-' + o.selectedIndex);
  return flag;
}
