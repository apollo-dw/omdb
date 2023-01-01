<?php
    $PageTitle = "Charts";
    require '../header.php';
?>

<h1 id="heading">Top Rated Maps of All Time</h1>

<style>
	.flex-container{
		display: flex;
		width: 100%;
	}
	
	.diffContainer{
		background-color:DarkSlateGrey;
		align-items: center;
	}
	
	.diffBox{
		padding:0.5em;
		flex-grow: 1;
		height:100%;
	}
	
	.diffbox a{
		color: white;
	}
	
	.diffThumb{
		height: 80px;
		width: 80px;
		border: 1px solid #ddd;
		object-fit: cover;
	}
	
	.pagination {
		display: inline-block;
		color: DarkSlateGrey;
	}

	.pagination span {
		float: left;
		padding: 8px 16px;
		text-decoration: none;
		cursor: pointer;
	}
	
	.active {
		font-weight: 900;
		color: white;
	}
</style>

<div style="text-align:left;">
	<div class="pagination">
	  <span onClick="changePage(page-1)">&laquo;</span>
	  <span class="pageLink page1 active" onClick="changePage(1)" >1</span>
	  <span class="pageLink page2" onClick="changePage(2)" >2</span>
	  <span class="pageLink page3" onClick="changePage(3)" >3</span>
	  <span class="pageLink page4"  onClick="changePage(4)" >4</span>
	  <span class="pageLink page5" onClick="changePage(5)" >5</span>
	  <span class="pageLink page6" onClick="changePage(6)" >6</span>
	  <span class="pageLink page7" onClick="changePage(7)" >7</span>
	  <span class="pageLink page8" onClick="changePage(8)" >8</span>
	  <span class="pageLink page9" onClick="changePage(9)" >9</span>
	  <span onClick="changePage(page+1)">&raquo;</span>
	</div>
</div>

<div class="flex-container">
	<div id="chartContainer" class="flex-item" style="flex: 0 0 75%; padding:0.5em;">
		<?php
			include 'chart.php';
		?>
	</div>

	<div style="padding:1em;" class="flex-item">
		<span>Filters</span>
		<hr>
		<form>
			<select name="order" id="order" autocomplete="off" onchange="updateChart();">
				<option value="1" selected="selected">Highest Rated</option>
				<option value="2">Lowest Rated</option>
			</select> maps of
			<select name="year" id="year" autocomplete="off" onchange="updateChart();">
				<option value="-1" selected="selected">All Time</option>
				<option value="2007">2007</option>
				<option value="2008">2008</option>
				<option value="2009">2009</option>
				<option value="2010">2010</option>
				<option value="2011">2011</option>
				<option value="2012">2012</option>
				<option value="2013">2013</option>
				<option value="2014">2014</option>
				<option value="2015">2015</option>
				<option value="2016">2016</option>
				<option value="2017">2017</option>
				<option value="2018">2018</option>
				<option value="2019">2019</option>
				<option value="2020">2020</option>
				<option value="2021">2021</option>
				<option value="2022">2022</option>
                <option value="2023">2023</option>
			</select>
		</form>
		<span>Info</span>
		<hr>
		Chart is based on an implementation of the Bayesian average method.<br><br>
		The chart updates once every <b>hour.</b><br><br>
        Ratings are weighed based on user rating quality, one contributing factor being their rating distribution.
	</div>

</div>

<div style="text-align:left;">
	<div class="pagination">
	  <span onClick="changePage(page-1)">&laquo;</span>
	  <span class="pageLink page1 active" onClick="changePage(1)" >1</span>
	  <span class="pageLink page2" onClick="changePage(2)" >2</span>
	  <span class="pageLink page3" onClick="changePage(3)" >3</span>
	  <span class="pageLink page4"  onClick="changePage(4)" >4</span>
	  <span class="pageLink page5" onClick="changePage(5)" >5</span>
	  <span class="pageLink page6" onClick="changePage(6)" >6</span>
	  <span class="pageLink page7" onClick="changePage(7)" >7</span>
	  <span class="pageLink page8" onClick="changePage(8)" >8</span>
	  <span class="pageLink page9" onClick="changePage(9)" >9</span>
	  <span onClick="changePage(page+1)">&raquo;</span>
	</div>
</div>

<script>
	const numOfPages = <?php echo floor($conn->query("SELECT Count(*) FROM `beatmaps` WHERE `Rating` IS NOT NULL;")->fetch_row()[0] / 50) + 1; ?>;
	var page = 1;
	 
	function changePage(newPage) {
		page = Math.min(Math.max(newPage, 1), 9);
		updateChart();
	}
	
	function resetPaginationDisplay() {
		$(".pageLink").removeClass("active");
		
		var pageLink = '.page' + page;
		
		$(pageLink).addClass("active");
		
		var year = document.getElementById("year").value;
		var order = document.getElementById("order").value;
		
		var orderString = "Top Rated ";
		
		if (order == 2){
			orderString = "Lowest Rated ";
		}
		
		if (year == -1){
			document.getElementById("heading").innerHTML = orderString + "Maps of All Time";
		} else {
			document.getElementById("heading").innerHTML = orderString +  "Maps of " + year;
		}
	}
	 
	function updateChart() {
		var year = document.getElementById("year").value;
		var order = document.getElementById("order").value;
		var xmlhttp=new XMLHttpRequest();
		xmlhttp.onreadystatechange=function() {
			if (this.readyState==4 && this.status==200) {
				document.getElementById("chartContainer").innerHTML=this.responseText;
				resetPaginationDisplay();
			}
		}
		xmlhttp.open("GET","chart.php?y=" + year + "&p=" + page + "&o=" + order,true);
		xmlhttp.send();
	}
</script>

<?php
    require '../footer.php';
?>