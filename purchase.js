var list, count, links, index, result, howMany, __iterator, iter, __next, __hasNext;
var detailUrls = [];
var output = [];

var casper = require('casper').create({
	verbose: true,
	logLevel: 'debug',
	clientScripts: ["js/jquery.min.js"]
});
var x = require('casper').selectXPath;
var fs = require('fs');

casper.userAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36 ');
var url = "https://www.amazon.co.jp/gp/css/order-history?ie=UTF8&ref_=ya_orders_ap&";

casper.start(url, function() {
	console.log(this.getTitle());
});

casper.then(function(){
  this.evaluate(function(id,pass) {
    document.querySelector('#ap_email').value = id;
    document.querySelector('#ap_password').value = pass;
    document.querySelector('#signInSubmit-input').click();
  }, "id", "pass");
});

casper.wait(3000, function() {
  this.echo("I've waited for a second.");
});


casper.then(function(){
	links = this.evaluate(function() {
		links = [];

    Array.prototype.forEach.call(__utils__.findAll('div.order-links a'), function(e) {
      var strUrl = new String(e.href);
      if(strUrl.indexOf("edit.html") !== -1) {
        links.push(e.href);
      }
    });
    return links;
  });
});


casper.then(function() {
  __iterator = function (collection, howMany) {
    count = 0;
    __next = function() {
      index = howMany * count;
      result = collection.slice(index, index + howMany);
      count += 1;
      return result;
    };
    __hasNext = function() {
      index = howMany * count;
      return collection.slice(index, index + howMany).length > 0;
    };
    return {next: __next, hasNext: __hasNext};
  };

  iter = __iterator(links, 1);
  while(iter.hasNext()) {
    var _urls = iter.next();
    var url = new String(_urls);
    detailUrls.push(url);
  }

  console.log(links.length);
});

casper.then(function() {
  __iterator = function (collection, howMany) {
    count = 0;
    __next = function() {
      index = howMany * count;
      result = collection.slice(index, index + howMany);
      count += 1;
      return result;
    };
    __hasNext = function() {
      index = howMany * count;
      return collection.slice(index, index + howMany).length > 0;
    };
    return {next: __next, hasNext: __hasNext};
  };

  detailUrls.forEach(function(url) {
    console.log("@@@@@@");
    console.log(url);

    casper.then(function() {
      this.open(url);
    });

    casper.then(function() {
      var orders;
      orders = this.evaluate(function() {
        orders = [];
        Array.prototype.forEach.call(__utils__.findAll('table[style="border: 1px solid #DCDCDC; padding:10px; border-radius: 6px;"]'), function(e) {
          orders.push(e.innerText);
        });
        return orders;
      });

      var productURL;
      productURL = this.evaluate(function() {
        productURL = [];
        Array.prototype.forEach.call(__utils__.getElementsByXPath('//td/div[1]/a'), function(e) {
          productURL.push(e.getAttribute('href'));
        });
        return productURL;
      });

      iter = __iterator(orders, 1);

      console.log("");
      console.log("++++++++++");
      productURL.forEach(function(u) {
        console.log("url = " + u);
      });

      var _data = {
        "purchaseShop":"Amazon",
        "detailUrl":url,
      };

      var tablesCount = 0;
      while(iter.hasNext()) {
        console.log("");
        console.log("tablesCount = " + tablesCount);

        var _items = iter.next();

        //var itemsArray = _items[0].split($new_line);
        var itemsArray = _items[0].split(/\r\n|\r|\n/);

        var count = 0;      // Loop counter
        var itemCount = 1;  // Count of items in the order of 1

        var nextItemNamePos = 12;
        var nextPricePos = 16;

        var isValid = true;

        itemsArray.forEach(function(item) {
          console.log(count + " : " + item);

          // Table for about an order number
          if(tablesCount == 0) {

            // Order date
            if(count == 0) {
              var detail = item.split(/注文日：/);
              _data["orderDate"] = detail[1].replace(/^\s+|\s+$/g,'');
            }
          }

          // Table for the order details
          if(tablesCount == 1) {

            if(item.match(/注文商品/)) {
              nextItemNamePos = count + 1;
              nextPricePos = count + 5;
            }

            // Completed when the "subtotal" comes
            if(item.match(/商品の小計/)) {
              var detail = item.split(/￥/);
              _data["totalPrice"] = detail[1].replace(/^\s+|\s+$/g,'');

              isValid = false;
              count++;
              return;
            }

            // Required data is not in the sequence already
            if(!isValid) {
              count++;
              return;
            }

            // Empty data is ignored
            if(item.length == 0) {
              count++;
              return;
            }

            // Product name
            if(count == nextItemNamePos) {
              console.log("itemName IN (count =" + count + ")");

              var detail = item.split(/点/);
              _data["purchaseNums" + itemCount] = detail[0].replace(/^\s+|\s+$/g,'');
              _data["itemName" + itemCount] = String(detail[1]).replace(/^\s+|\s+$/g,'');

              console.log("itemCount = " + itemCount + " productURL = " + productURL[itemCount]);
              // array adjusted so that starting from 1 to itemCount
              _data["productURL" + itemCount] = productURL[itemCount - 1];

              nextItemNamePos = nextItemNamePos + 5;
            }

            // price
            if(count == nextPricePos) {

              if(item.match(/￥ /)) {
                console.log("itemPrice IN (count =" + count + ")");
                var detail = item.split(/￥/);
                _data["itemPrice" + itemCount] = detail[1].replace(/^\s+|\s+$/g,'');
                nextPricePos = nextPricePos + 5;
                itemCount++;
              }
              else {
                nextPricePos++;

                // Adjustment item name so that the next does not come before
                nextItemNamePos = nextPricePos + 1;
              }
            }
          }

          count++;
        });

        tablesCount++;
      }

      output.push(JSON.stringify(_data) + "\n");
    });
  });
});

casper.then(function(){
  console.log(output);
  fs.write("json/purchase.json", output, 'w');
});

casper.run(function() {
 //require('utils').dump(this.links);
 this.exit();
});
