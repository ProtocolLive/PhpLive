function WindowOpen(Title){
  document.getElementById("AjaxWindow1Title").innerText = Title;
  document.getElementById("AjaxWindow1").style.top = "50%";
  document.getElementById("AjaxWindow1").style.left = "50%";
  document.getElementById("AjaxWindow1").style.transform = "translate(-50%, -50%)";
  document.getElementById("AjaxWindow1").style.visibility = "visible";
  document.getElementById("AjaxWindowBg").style.visibility = "visible";
}
function WindowClose(){
  document.getElementById("AjaxWindowBg").style.visibility = "hidden";
  document.getElementById("AjaxWindow1").style.visibility = "hidden";
  document.getElementById("AjaxWindow1-1").style.visibility = "hidden";
}