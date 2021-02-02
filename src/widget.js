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
const url = "build/data.json";
const css = `{{css}}`;
const widget = document.getElementById("czw");

fetch(url)
  .then(function (r) {
    if (!r.ok) {
      throw Error(r);
    }
    return r.json();
  })
  .then(function (r) {
    const y = parseInt(r.year);
    const half = (y - (y/2)) / 2 + (y/2);
    widget.insertAdjacentHTML("beforeEnd", css);
    widget.insertAdjacentHTML(
      "beforeEnd",
      `<div class="czw__title">\
 			<span class="czw__title__text">CO<sub>2</sub></span>\
            <span class="czw__title__date">${r.date}</span>\
       </div>\
		 <div class="czw__graph">\
			 <div class="czw__graph__selector">\
			 	<span class="czw__graph__selector__text active">1000 yrs</span>\
				<span class="czw__graph__selector__text ">20 yrs</span>\		 
			 </div>\
            <div class="czw__graph__wrapper">\
            ${decodeURIComponent(r.chart20)}
            ${decodeURIComponent(r.chart)}
    			 <div class="czw__graph__labels">\
					 <div class="czw__graph__labels czw__graph__labels--y">\ 
					 	 <span class="czw__graph__labels__label czw_400">300 ppm</span>\
    					 <span class="czw__graph__labels__label czw_300">400 ppm</span>\
    					 <span class="czw__graph__labels__label czw_380">380 ppm</span>\
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
      <div class="czw__stats__top">\
        <div class="czw__stats__top czw__stats__top--avg">\
          <span class="czw__stats__label">7-day average</span>\
          <span class="czw__stats__avg-value"><span>${r.average}</span><span class="ppm">ppm</span></span>\
        </div>\
      </div>\
      <div class="czw__stats__bottom">\
        <div class="czw__stats__bottom czw__stats__bottom--increase">\
          <span class="czw__stats__label">In last 2 years</span>\
          <div class="czw__gauge">
            <svg width="184" height="184" fill="none" xmlns="http://www.w3.org/2000/svg" class="czw__gauge__graphic"><path d="M183.078 93.378a91.217 91.217 0 01-25.609 62.084l-4.592-4.437a84.828 84.828 0 0023.816-57.738l6.385.09z" fill="#E42C2D"/><path d="M93.168.873a91.217 91.217 0 0162.085 25.608l-4.437 4.592A84.832 84.832 0 0093.078 7.258l.09-6.385z" fill="#FEC432"/><path d="M.877 91.003a91.217 91.217 0 0125.678-62.156l4.587 4.442a84.832 84.832 0 00-23.88 57.805l-6.385-.091z" fill="#29BA7B"/><path d="M157.281 28.505a91.219 91.219 0 0125.792 62.008l-6.384.11a84.834 84.834 0 00-23.987-57.668l4.579-4.45z" fill="#F2994A"/><path d="M28.293 26.67A91.217 91.217 0 0190.3.876l.11 6.384a84.832 84.832 0 00-57.668 23.987l-4.45-4.579z" fill="#6ED759"/><path d="M26.668 155.876A91.215 91.215 0 01.877 93.891l6.384-.112a84.832 84.832 0 0023.986 57.647l-4.579 4.45z" fill="#009299"/></svg>
            <span class="czw__gauge__arrow" style="transform:rotate(${r.angle}deg)">
              <svg width="23" height="104" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.5 24L2.407 6h18.186L11.5 24z" fill="#fff" stroke="#3F3F3F" stroke-width="4" stroke-linejoin="round"/></svg>
            </span>
            <span class="czw__stats__value"><span>${r.change}</span><span class="ppm">ppm</span></span>\
          </div>
        </div>\
      </div>\
 		</div>`
    );

    const svg = widget.getElementsByClassName("chart2000")[0];

    // define last year
    let end = new Date().getFullYear() - 1;
    let mid = end - 10;
    let start = end - 19;

    const selectBtns = widget.getElementsByClassName(
      "czw__graph__selector__text"
    );

    for (var i = 0; i < selectBtns.length; i++) {
      selectBtns[i].addEventListener("click", function () {
        var current = document.getElementsByClassName("active");
        current[0].className = current[0].className.replace(" active", "");
        this.className += " active";
      });
    }

      //long term data view
    selectBtns[0].addEventListener("click", function () {
      if (this.classList.contains("active")) {
        widget.getElementsByClassName("chart2000")[0].style.visibility =
          "visible";
        widget.getElementsByClassName("czw__graph__latest")[0].style.visibility =
          "visible";
        widget.getElementsByClassName("chart20")[0].style.visibility = "hidden";
        widget.getElementsByClassName("czw_400")[0].style.visibility = "visible";
        widget.getElementsByClassName("czw_380")[0].style.visibility ="hidden";
        widget.getElementsByClassName("czw_300")[0].style.bottom =
          (parseInt(y300.getAttribute("y1")) / height) * 100 + "%";
        widget.getElementsByClassName(
          "czw__graph__labels__label--first"
        )[0].innerHTML = "1000 AD";
        widget.getElementsByClassName(
          "czw__graph__labels__label--mid"
        )[0].innerHTML = "1500 AD";
        widget.getElementsByClassName(
          "czw__graph__labels__label--end"
        )[0].innerHTML = "Now";
      }
    });
//last 20 years view
    selectBtns[1].addEventListener("click", function () {
      if (this.classList.contains("active")) {
        widget.getElementsByClassName("chart2000")[0].style.visibility =
          "hidden";
        widget.getElementsByClassName("czw__graph__latest")[0].style.visibility =
          "hidden";
        widget.getElementsByClassName("chart20")[0].style.visibility =
          "visible";
        widget.getElementsByClassName("czw_380")[0].style.visibility = "visible"
        widget.getElementsByClassName("czw_400")[0].style.visibility = "hidden"; //the 400 and 300 class names seem to be the wrong way around?
        widget.getElementsByClassName("czw_380")[0].style.bottom = "17%"; // would be good to make this calculated from the data...
        widget.getElementsByClassName("czw_300")[0].style.bottom = (parseInt(y300.getAttribute("y1")) / height) * 80 + "%";
        widget.getElementsByClassName(
          "czw__graph__labels__label--first"
        )[0].innerHTML = start;
        widget.getElementsByClassName(
          "czw__graph__labels__label--mid"
        )[0].innerHTML = mid;
        widget.getElementsByClassName(
          "czw__graph__labels__label--end"
        )[0].innerHTML = end;
      }
    });

    const y400 = svg.getElementsByClassName("y400")[0];
    const y300 = svg.getElementsByClassName("y300")[0];

    const height = parseInt(svg.dataset.height);

    widget.getElementsByClassName("czw_300")[0].style.bottom =
      (parseInt(y300.getAttribute("y1")) / height) * 100 + "%";
    widget.getElementsByClassName("czw_400")[0].style.bottom =
      (parseInt(y400.getAttribute("y1")) / height) * 100 + "%";
    widget.getElementsByClassName("czw_380")[0].style.visibility ="hidden";
  })
  .catch(function (e) {
    // Handle error responses
    widget.innerHTML = "No Result";
    console.log(e);
  });
