function Drag(Janela, Bar){
  let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  Janela = document.getElementById(Janela);
  Bar.onmousedown = DragMouseDown;
  function DragMouseDown(e){
    e = e || window.event;
    e.preventDefault();
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmousemove = ElementDrag;
    document.onmouseup = CloseDragElement;
  }
  function ElementDrag(e){
    e = e || window.event;
    e.preventDefault();
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    Janela.style.top = (Janela.offsetTop - pos2) + "px";
    Janela.style.left = (Janela.offsetLeft - pos1) + "px";
  }
  function CloseDragElement(){
    document.onmouseup = null;
    document.onmousemove = null;
  }
}