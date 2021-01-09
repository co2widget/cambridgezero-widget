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
const url = "{{url}}/build/data.json";
const css = "{{css}}";
const widget = document.getElementById("czw");

fetch(url)
  .then(function (r) {
    if (!r.ok) {
      throw Error(r);
    }
    return r.json();
  })
  .then(function (r) {
    const half = (parseInt(r.year) - 1010) / 2 + 1010;
    widget.insertAdjacentHTML("beforeEnd", css);
    widget.insertAdjacentHTML(
      "beforeEnd",
      `<div class="czw__title">\
 			<span class="czw__title__text">CO<sub>2</sub></span>\
            <span class="czw__title__date">${r.date}</span>\
       </div>\
		 <div class="czw__graph">\
		 	<div class="czw__graph__selector"></div>\
            <div class="czw__graph__wrapper">\
    			 ${decodeURIComponent(r.chart)}
    			 <div class="czw__graph__labels">\
    			 	<div class="czw__graph__labels czw__graph__labels--y">\ 
    					 <span class="czw__graph__labels__label czw_400">300 ppm</span>\
    					 <span class="czw__graph__labels__label czw_300">400 ppm</span>\
    			 	</div>\ 
    				<div class="czw__graph__labels czw__graph__labels--x">\ 
    					 <span class="czw__graph__labels__label czw__graph__labels__label--first">1000 AD</span>\
    					 <span class="czw__graph__labels__label czw__graph__labels__label--mid">1500 AD</span>\
    					 <span class="czw__graph__labels__label  czw__graph__labels__label--end">Now</span>\
					</div>\
    			 </div>\
                 <div class="czw__graph__latest"></div>\
            </div>\
		 </div>\
 		<div class="czw__stats">\
 			<div class="czw__stats__single czw__stats__single--avg">\
 				<span class="czw__stats__label">7-day average</span>\
 				<span class="czw__stats__value">${r.average} ppm</span>\
 			</div>\
 			<div class="czw__stats__single czw__stats__single--increase">\
 				<span class="czw__stats__label">In last 2 years</span>\
 				<span class="czw__stats__value">${r.change} ppm</span>\
 			</div>\
 		</div>`
    );

    const svg = widget.getElementsByClassName("chart")[0];
    const y400 = svg.getElementsByClassName("y400")[0];
    const y300 = svg.getElementsByClassName("y300")[0];

    const height = parseInt(svg.dataset.height);

    widget.getElementsByClassName("czw_400")[0].style.bottom =
      (parseInt(y400.getAttribute("y1")) / height) * 100 + "%";
    widget.getElementsByClassName("czw_300")[0].style.bottom =
      (parseInt(y300.getAttribute("y1")) / height) * 100 + "%";
  })
  .catch(function (e) {
    // Handle error responses
    widget.innerHTML = "No Result";
    console.log(e);
  });
