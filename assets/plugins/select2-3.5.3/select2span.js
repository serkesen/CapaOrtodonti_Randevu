function select2FormatResult(i) {
  var markup = ''+
  '<div class="row-fluid f-80" style="border-bottom:1px #003764 solid;">' +
    '<div class="span3"><img src="' + i.Avatar + '" class="img-responsive" style="max-width:65px;"></div>' +
    '<div class="span9">' +
      '<div class="row-fluid">' +
        '<div class="span6">' + i.LeftText + '</div>' +
        '<div class="span6">' + i.RightText + '</div>' +
      '</div>'+
      ((i.FullText) ? '<div>' + i.FullText + '</div>' : '')+
    '</div>'+
  '</div>';
  return markup;
}

function select2FormatSelection(i) { return i.LeftText; }
