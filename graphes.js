function creerPie(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee){
	// Pour ne pas avoir l'animation au chargement de la page
	var options = {
					animation : false,
					showTooltips: false,
					//tooltipFontSize: 10,
					//tooltipTemplate: "<%if (label) {%> <%=parseInt(value)%>% <%=label%>  <%}%>",
	};
	var pieData = [
					{
						label: "achevé",
						value: pourcentage_act_achevee,
						color:"#46BFBD"
					},
					{
						 
						value : pourcentage_act_non_achevee,
						color : "#E0E4CC"
					}
				];

		var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}


function creerPieDetaille(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee){
	
	var options = {
					animation : false,// Pour ne pas avoir l'animation au chargement de la page
					showTooltips: false,
	};
	var pieData = [
					{
						label: "achevé",
						value: pourcentage_act_achevee,
						color:"#36AFAD"
					},
					{
						label: "non achevé",
						value : pourcentage_act_non_achevee,
						color : "#D8DCC4"
					}
				];

		var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}
function creerPieProf(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee){
	
	var options = {
					animation : false,// Pour ne pas avoir l'animation au chargement de la page
					showTooltips: false
	};
	var pieData = [
					{
						label: "achevé",
						value: pourcentage_act_achevee,
						color:"#0088CC"
					},
					{
						label: "non achevé",
						value : pourcentage_act_non_achevee,
						color : "#E0E4CC"
					}
				];

		var myPie = new Chart(document.getElementById(nomCanvas).getContext("2d")).Pie(pieData,options);
}

function creerDoughnut(nomCanvas, pourcentage_act_achevee, pourcentage_act_non_achevee){
	var options = {animation : false};
	var doughnutData = [
			{
				value: pourcentage_act_achevee,
				color:"#46BFBD"
			},
			{
				value : pourcentage_act_non_achevee,
				color : "#E0E4CC"
			}		
		];

	var myDoughnut = new Chart(document.getElementById(nomCanvas).getContext("2d")).Doughnut(doughnutData,options);
}

/* Fonction utilisee pour avoir des valeurs entieres sur l'axe des ordonnees pour l'histogramme => trouvee sur internet 
 * Modifie des options
 */
/*function wholeNumberAxisFix(data) {
   var maxValue = false;
   for (datasetIndex = 0; datasetIndex < data.datasets.length; ++datasetIndex) {
       var setMax = Math.max.apply(null, data.datasets[datasetIndex].data);
       if (maxValue === false || setMax > maxValue) maxValue = setMax;
   }

   var steps = new Number(maxValue);
   var stepWidth = new Number(1);
   if (maxValue > 10) {
       stepWidth = Math.floor(maxValue / 5);
       steps = Math.ceil(maxValue / stepWidth);
   }
   return { scaleOverride: true, scaleSteps: steps, scaleStepWidth: stepWidth, scaleStartValue: 0 };

return data;
}
*/
function creerBar(nomCanvas, tableauHisto){
	var barChartData = {
			labels : ["0","10","20","30","40","50","60","70","80","90","100"],
			datasets : [
				{
					/*label: "My First dataset",*/
					fillColor: "rgba(151,187,205,0.5)",
					strokeColor: "rgba(151,187,205,0.8)",
					highlightFill: "rgba(151,187,205,0.75)",
					highlightStroke: "rgba(151,187,205,1)",

					//legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",

					data : tableauHisto
				}/*,
				{
					fillColor : "rgba(220,220,220,0.5)",
					strokeColor : "rgba(220,220,220,1)",
					data : [65,59,90,81,56,55,40]
				}*/
			]
			
		}
		var options = {
			tooltipTemplate: "<%if (label){%> <%= value %> étudiant(s) sont à <%=label%>% du cours<%}%>",
		}

	var myLine = new Chart(document.getElementById(canvasHisto).getContext("2d")).Bar(barChartData, options);	//wholeNumberAxisFix(barChartData));
}