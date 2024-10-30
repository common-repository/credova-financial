document.addEventListener("DOMContentLoaded", function() {
 var cExists = document.getElementById("credova_reset-to-default-link");
 if(cExists){
  var credova_wooview = document.getElementById("credova-wooview-id");
  credova_wooview.setAttribute("style", "display:none;");
  document.getElementById("credova_reset-to-default-link").onclick = function(){ 
    document.getElementById("woocommerce_credova_aslowasproduct_hook").value = "woocommerce_single_product_summary";
    document.getElementById("woocommerce_credova_aslowasproduct_priority").value = "15";
  }
 }
});