/*
 *	Onload
 *  <div id="czw" class="czw"></div>
 *  <script src="urltothisscript" defer></script>
 *
 *	Complete output
 *  <div id="czw" class="czw">
 *		<div class="czw__title">
 *			<span class="czw__title__text">CO2</span>
 *      </div>
 *		<div class="czw__graph">
 *			<!-- graph output -->
 *		</div>
 *		<div class="czw__stats">
 *			<div class="czw__stats__single czw__stats__single--avg">
 *				<span class="czw_stats__label"></span>
 *				<span class="czw_stats__value"></span>
 *			</div>
 *			<div class="czw__stats__single czw__stats__single--increase">
 *				<span class="czw_stats__label"></span>
 *				<span class="czw_stats__value"></span>
 *			</div>
 *		</div>
 *	</div>
 */


// Pseudo code

// set up constants
// CSS
// widget elements

// Grab data using fetch - saved files on server
// Process data
// Output HTML scructure in correct place with replaced values
const css = '{{css}}';
const url = '{{url}}/build/data.json';
const widget = document.getElementById('czw');



fetch(url).then(function (r) {
    if (!r.ok) {
        throw Error(r);
    }
    return r.json();
}).then(function (r) {
    widget.insertAdjacentHTML('beforeEnd',css);
    widget.insertAdjacentHTML('beforeEnd',`<div id="czw" class="czw">\
 		<div class="czw__title">\
 			<span class="czw__title__text">CO2</span>\
       </div>\
 		<div class="czw__graph">\
 			${decodeURIComponent(r.chart)}
 		</div>\
 		<div class="czw__stats">\
 			<div class="czw__stats__single czw__stats__single--avg">\
 				<p class="czw_stats__label">7-day average</p>\
 				<p class="czw_stats__value">${r.average}</p>\
 			</div>\
 			<div class="czw__stats__single czw__stats__single--increase">\
 				<p class="czw_stats__label">In last 2 years</p>\
 				<p class="czw_stats__value">${r.change}</p>\
 			</div>\
 		</div>\
	 </div>`);
	 
	// // use d3 select to find chart class and add axis?
	// // add axis to chart
	// var svg = d3.select('.chart')
	// const x = d3.scaleTime().range([0, width]);
	// var xAxis = d3.axisBottom(x)

	// svg.append('g')
    //         .attr('class', 'x axis')
    //         .attr('transform', 'translate(0,' + height + ')')
	// 		.call(xAxis)
	// // add to html
	// widget.insertAdjacentHTML('afterBegin', )	

}).catch(function (e) {
	// Handle error responses
    widget.innerHTML = 'No Result';
    console.log(e);
});

//const svg = document.getElementsByClassName('chart');


