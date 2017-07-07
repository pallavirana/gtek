jQuery(document).ready(function($){
    // Dasheed Line
    var s  = Snap("#dashed-line"); 
    var sc = $("#dashed-line");
    var color = sc.data('color').toString();
    var tux = Snap.load("assets/img/dashed-line.svg", function ( f ) {
        var p = f.select("#path"); 
        p.attr({
            fill: color,
            strokeWidth: 0
        });
        s.append(f);
    } );

});