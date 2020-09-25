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
const url = '{{url}}/data.json';
const widget = document.getElementById('czw');



fetch(url).then(function (r) {
    if (!r.ok) {
        throw Error(r);
    }
    return r.json();
}).then(function (r) {
	console.log(r);
    widget.insertAdjacentHTML('beforeEnd',css);
    widget.insertAdjacentHTML('beforeEnd',`<div id="czw" class="czw">\
 		<div class="czw__title">\
 			<span class="czw__title__text">CO2</span>\
       </div>\
 		<div class="czw__graph">\
 			${r.chart}
 		</div>\
 		<div class="czw__stats">\
 			<div class="czw__stats__single czw__stats__single--avg">\
 				<span class="czw_stats__label">7-day average</span>\
 				<span class="czw_stats__value">${r.average}</span>\
 			</div>\
 			<div class="czw__stats__single czw__stats__single--increase">\
 				<span class="czw_stats__label"></span>\
 				<span class="czw_stats__value">${r.change}</span>\
 			</div>\
 		</div>\
 	</div>`);
}).catch(function (e) {
	// Handle error responses
    widget.innerHTML = 'No Result';
    console.log(e);
});
