$(document).ready(function() {
    $('#osuLink').on('click', function() {
        setGameMode(0)
    });

    $('#taikoLink').on('click', function() {
        setGameMode(1)
    });

    $('#catchLink').on('click', function() {
        setGameMode(2)
    });

    $('#maniaLink').on('click', function() {
        setGameMode(3)
    });
});

function setGameMode(mode) {
    var expirationDate = new Date();
    expirationDate.setFullYear(expirationDate.getFullYear() + 1);

    var cookieValue = "mode=" + mode + "; expires=" + expirationDate.toUTCString() + ";path=/;";
    document.cookie = cookieValue;
    location.reload();
}

function showResult(str) {
    if (str.length==0) {
        document.getElementById("topBarSearchResults").innerHTML="";
        document.getElementById("topBarSearchResults").style.display="none";
        return;
    }
    var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange=function() {
        if (this.readyState==4 && this.status==200) {
            document.getElementById("topBarSearchResults").innerHTML=this.responseText;
            document.getElementById("topBarSearchResults").style.display="block";
        }
    }
    xmlhttp.open("GET","/beatmapSearch.php?q="+str,true);
    xmlhttp.send();
}

function searchFocus() {
    document.getElementById("topBarSearchResults").style.display="block";
}