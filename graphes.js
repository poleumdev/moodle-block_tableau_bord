function creerPie(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee){
    // Pour ne pas avoir l'animation au chargement de la page.
    var options = {
        animation : false,
        showTooltips: false,
    };
    var pieData = [
        {label: "achevé", value: pourcentage_act_achevee, color: "#46BFBD"},
        {value: pourcentage_act_non_achevee, color: "#E0E4CC"}];

    var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}

function creerPieDetaille(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee) {
    var options = {
        animation : false,// Pour ne pas avoir l'animation au chargement de la page.
        showTooltips: false,
    };
    var pieData = [
        {label: "achevé",     value: pourcentage_act_achevee,     color: "#36AFAD"},
        {label: "non achevé", value: pourcentage_act_non_achevee, color: "#D8DCC4"}];

    var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}

function creerPieProf(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee) {
    var options = {animation : false, showTooltips: false};
    var pieData = [
        {label: "achevé",     value: pourcentage_act_achevee, color: "#0088CC"},
        {label: "non achevé", value: pourcentage_act_non_achevee, color: "#E0E4CC"}];

    var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}

function creerDoughnut(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee) {
    var options = {animation : false};
    var doughnutData = [
        {value: pourcentage_act_achevee,     color: "#46BFBD"},
        {value: pourcentage_act_non_achevee, color: "#E0E4CC"}];

    var myDoughnut = new Chart(document.getElementById(nomCanvas).getContext("2d")).Doughnut(doughnutData,options);
}

function creerBar(nomCanvas, tableauHisto) {
    var barChartData =
        {   labels : ["0","10","20","30","40","50","60","70","80","90","100"],
            datasets : [{fillColor: "rgba(151,187,205,0.5)",
                strokeColor: "rgba(151,187,205,0.8)",
                highlightFill: "rgba(151,187,205,0.75)",
                highlightStroke: "rgba(151,187,205,1)",
                data : tableauHisto }]};

    var options = {
        tooltipTemplate: "<%if (label){%> <%= value %> étudiant(s) sont à <%=label%>% du cours<%}%>",
    }

    var myLine = new Chart(document.getElementById(canvasHisto).getContext("2d")).Bar(barChartData, options);
}