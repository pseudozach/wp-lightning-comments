document.addEventListener('DOMContentLoaded', function(){
  var LightningComments = 'lncomments';

  // // check localStorage if user previously paid and set the hidden value
  // var correctId = LightningComments + location.pathname + questions.map(function(q){return q.correct}).join('');
  // if(localStorage.getItem(correctId) === correctId){ 
  //   var fool = document.getElementById("pmg_comment_titlez");
  //   fool.value = "youreok";
  // }

  var parseQuiz = function(str){
    try{return JSON.parse(decodeURIComponent(str || ''))}
    catch(err){return []}
  };

  var removeQuizDelay = function (quizNode, formNode) {
    quizNode.style.height = quizNode.offsetHeight + 'px';
    formNode.style.height = formNode.scrollHeight + 'px';
    quizNode.style.height = '0px';

    // console.log("removequiz setTimeout");
    quizNode.style.display = 'none';
    formNode.style.height = 'auto';
  //console.log("iframe");
      // Make sure you are sending a string, and to stringify JSON
  //setTimeout(window.parent.postMessage(msg, '*'), 1500);
  // payiframe.style.display = "none";
  };

  var removeQuiz = function(quizNode, formNode){
    quizNode.style.height = quizNode.offsetHeight + 'px';
    formNode.style.height = formNode.scrollHeight + 'px';
    quizNode.style.height = '0px';
    // console.log("inside removequiz");
    setTimeout(function(){
      console.log("removequiz setTimeout");
      quizNode.style.display = 'none';
      formNode.style.height = 'auto';
    }, 1500);
  };

  var buildAnswer = function(text, name, value){
    var label = document.createElement('label');
    label.style.visibility = 'hidden';
    var input = label.appendChild(document.createElement('input'));
    var title = label.appendChild(document.createTextNode(text));

    input.type = 'radio';
    input.name = name;
    input.value = value;
    return label;
  };

  var buildQuiz = async function(quizNode){
    var formNode = quizNode.nextElementSibling;
    var errorText = quizNode.getAttribute('data-' + LightningComments + '-error');
    var questions = parseQuiz(quizNode.getAttribute('data-' + LightningComments));

    // correctId (key for localstorage for marking if a visitor paid the comment fee) depends on payperpost setting
    const payperpost = document.getElementsByName("lncomments_settings[lncomments_checkbox_field_0]")[0].value
    var correctId = LightningComments + location.pathname + questions.map(function(q){return q.correct}).join('');
    if(payperpost) {
      correctId = LightningComments + location.host + questions.map(function(q){return q.correct}).join('');
    }
    var errorNode = document.createElement('h3').appendChild(document.createTextNode(errorText)).parentNode;
    var container = document.createElement('div');

    if(localStorage.getItem(correctId) === correctId){  //Skip quiz if already solved
      var fool = document.getElementById("pmg_comment_titlez");
      fool.value = "youreok";
      return quizNode.parentNode.removeChild(quizNode);
    }

    questions.forEach(function(question, index){
      var h2el = document.createElement('h2');
      h2el.style.visibility = 'hidden';
      container.appendChild(h2el).textContent = question.text;  //Render title
      question.answer
        .map(function(key, val){return key && buildAnswer(key, LightningComments + index, val)})
        .sort(function(){return 0.5 - Math.random()})
        .forEach(function(node){node && container.appendChild(node)});
    });

    quizNode.appendChild(container);
    quizNode.addEventListener('change', function(){
      var checked = questions.map(function(q,i){return container.querySelector('input[name="' + LightningComments + i + '"]:checked')});
      var correct = questions.every(function(q,i){return checked[i] && Number(checked[i].value) === Number(q.correct)});
      var failure = !correct && checked.filter(Boolean).length === questions.length;

      if(correct){
        localStorage.setItem(correctId, correctId);
        removeQuiz(quizNode, formNode);
      }else if(failure){
        container.appendChild(errorNode);
      }
    });

    //testnet / mainnet - remove testnet it's not 2018 anymore!
    // var testnet = document.getElementsByName("lncomments_settings[lncomments_checkbox_field_0]")[0].checked;
    // var iframe = document.createElement('iframe');

    let invoice;
    const invoiceCheckInterval = 5000;

    var invoiceDiv = document.createElement('div');
    // invoiceDiv.style.height = '550px';
    invoiceDiv.style.width = '100%';
    invoiceDiv.style.transition = 'height 1s';
    const invoiceAmount = document.getElementsByName("lncomments_settings[lncomments_text_field_3]")[0].value
    const lnbitsUrl = document.getElementsByName("lncomments_settings[lncomments_text_field_1]")[0].value
    const lnbitsKey = document.getElementsByName("lncomments_settings[lncomments_text_field_2]")[0].value
    const btcpayserverUrl = document.getElementsByName("lncomments_settings[lncomments_text_field_4]")[0].value
    const btcpayserverStoreId = document.getElementsByName("lncomments_settings[lncomments_text_field_5]")[0].value
    const btcpayserverToken = document.getElementsByName("lncomments_settings[lncomments_text_field_6]")[0].value

    try {
      if(lnbitsUrl && lnbitsKey) {
        // fetch invoice from lnbits
        const invoiceResponse = await fetch(`${lnbitsUrl}/api/v1/payments`,
        {
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Api-Key': lnbitsKey
            },
            method: "POST",
            body: JSON.stringify({"out": false, "amount": parseInt(invoiceAmount), "memo": "Pay to comment on " + window.location.host})
        })
        invoice = await invoiceResponse.json()
        console.log('invoice: ', invoice)
        
        if(invoice?.payment_request) {
          // check if invoice is paid
          const interval = setInterval(async() => {
            const paidResponse = await fetch(`${lnbitsUrl}/api/v1/payments/${invoice.payment_hash}`,
            {
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Api-Key': lnbitsKey
              },
            })
            const paid = await paidResponse.json()
            if(paid?.paid) {
              localStorage.setItem(correctId, correctId);
              var fool = document.getElementById("pmg_comment_titlez");
              fool.value = "youreok";
              setTimeout(removeQuizDelay, 2500, quizNode, formNode);
              clearInterval(interval)
            }
          }, invoiceCheckInterval)
        }
      } else if(btcpayserverUrl && btcpayserverStoreId && btcpayserverToken) {
        // fetch invoice from lnbits
        const invoiceResponse = await fetch(`${btcpayserverUrl}/api/v1/stores/${btcpayserverStoreId}/invoices`,
        {
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': `token ${btcpayserverToken}`,
            },
            method: "POST",
            body: JSON.stringify({"amount": parseInt(invoiceAmount), "currency": "SATS"})
        })
        invoice = await invoiceResponse.json()
        // console.log('notinvoice ', invoice)
        const actualInvoiceResponse = await fetch(`${btcpayserverUrl}/api/v1/stores/${btcpayserverStoreId}/invoices/${invoice.id}/payment-methods`,
        {
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': `token ${btcpayserverToken}`,
            },
        })
        actualInvoice = (await actualInvoiceResponse.json())[0].destination
        console.log('invoice: ', actualInvoice)
        invoice.payment_request = actualInvoice
        
        if(actualInvoice) {
          // check if invoice is paid
          const interval = setInterval(async() => {
            const paidResponse = await fetch(`${btcpayserverUrl}/api/v1/stores/${btcpayserverStoreId}/invoices/${invoice.id}`,
            {
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `token ${btcpayserverToken}`,
              },
            })
            const paid = await paidResponse.json()
            if(paid?.status === 'Settled') {
              localStorage.setItem(correctId, correctId);
              var fool = document.getElementById("pmg_comment_titlez");
              fool.value = "youreok";
              setTimeout(removeQuizDelay, 2500, quizNode, formNode);
              clearInterval(interval)
            }
          }, invoiceCheckInterval)
        }
      }

      var p1 = document.createElement('p')
      p1.style.textAlign = 'center'
      p1.innerHTML = 'Scan/Tap/Copy to Pay'
      var p2 = document.createElement('p')
      p2.style.wordBreak = 'break-all'
      p2.innerHTML = invoice?.payment_request
      var anchor = document.createElement('a')
      anchor.target = '_blank'
      var qrcode = document.createElement('canvas');
      qrcode.setAttribute("id","qr")
      qrcode.style.margin = 'auto'
      qrcode.style.display = 'block'
      anchor.append(qrcode)
      anchor.href = 'lightning:' + invoice?.payment_request?.toUpperCase()
      invoiceDiv.append(p1)
      invoiceDiv.append(anchor)
      invoiceDiv.append(p2)
  
      quizNode.appendChild(invoiceDiv);
  
      //add the qrcode after append
      new QRious({
        element: document.getElementById('qr'),
        value: invoice?.payment_request,
        padding: '4',
        size: 300,
      });

    } catch (error) {
      console.log('failed to create Lightning invoice: ', error.message)
    }

    // instead of cross-origin events - lets check if invoice was paid with interval
    // Listen to message from child window
    // bindEvent(window, 'message', function (e) {
    //     // results.innerHTML = e.data;
    //     // console.log("fromiframe: " + e.data);
    //     if(e.data == "fromclientjs") {
    //       // console.log("payment received!");
    //       correct = true;
    //       localStorage.setItem(correctId, correctId);
    //       // removeQuiz(quizNode, formNode);
    //       setTimeout(removeQuizDelay, 2500, quizNode, formNode);

    //       var fool = document.getElementById("pmg_comment_titlez");
    //       fool.value = "youreok";

    //     } else if(e.data == "buyclicked") {
    //       iframe.style.height = "650px";
    //     } else {
    //       // console.log("received something else from iframe");
    //     }
    // });

  };

  [].forEach.call(document.querySelectorAll('.' + LightningComments), buildQuiz);



  // zitoshi
  function bindEvent(element, eventName, eventHandler) {
      if (element.addEventListener){
          element.addEventListener(eventName, eventHandler, false);
      } else if (element.attachEvent) {
          element.attachEvent('on' + eventName, eventHandler);
      }
  }

  // console.log("php_vars: " + JSON.stringify(php_vars));

});
