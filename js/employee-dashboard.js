const SUPABASE_URL="https://db.sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY="sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";
const employeeId="<?= $_SESSION['user']['id'] ?>";

const video=document.getElementById("qrVideo");
const msg=document.getElementById("attendanceMsg");

async function fetchAttendance(){
    const tbody=document.querySelector("#attendanceTable tbody");
    tbody.innerHTML="";
    const res=await fetch(`${SUPABASE_URL}/attendance?user_id=eq.${employeeId}&order=date.desc`, {headers:{
        "apikey":SUPABASE_KEY,"Authorization":`Bearer ${SUPABASE_KEY}`
    }});
    const data=await res.json();
    data.forEach(record=>{
        const tr=document.createElement("tr");
        tr.innerHTML=`<td>${record.date}</td><td>${record.time_in||''}</td><td>${record.time_out||''}</td><td>${record.location||''}</td>`;
        tbody.appendChild(tr);
    });
}
fetchAttendance();

async function startScanner(){
    try{
        const stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:"environment"}});
        video.srcObject=stream;
        video.setAttribute("playsinline",true);
        video.play();
        requestAnimationFrame(scanFrame);
    }catch(err){console.error(err); showToast("Camera not accessible","error");}
}

async function scanFrame(){
    if(video.readyState===video.HAVE_ENOUGH_DATA){
        const canvas=document.createElement("canvas");
        canvas.width=video.videoWidth;
        canvas.height=video.videoHeight;
        const ctx=canvas.getContext("2d");
        ctx.drawImage(video,0,0,canvas.width,canvas.height);
        const imageData=ctx.getImageData(0,0,canvas.width,canvas.height);
        const code=jsQR(imageData.data,imageData.width,imageData.height);
        if(code){await markAttendance(code.data); return;}
    }
    requestAnimationFrame(scanFrame);
}

async function markAttendance(token){
    msg.textContent="";
    const tokenRes=await fetch(`${SUPABASE_URL}/qr_tokens?token=eq.${token}&is_active=eq.true`, {headers:{
        "apikey":SUPABASE_KEY,"Authorization":`Bearer ${SUPABASE_KEY}`
    }});
    const tokenData=await tokenRes.json();
    if(!tokenData.length){showToast("Invalid or expired QR code","error"); return;}
    const location=tokenData[0].location_id;
    const today=new Date().toISOString().split("T")[0];

    const attendanceRes=await fetch(`${SUPABASE_URL}/attendance?user_id=eq.${employeeId}&date=eq.${today}`, {headers:{
        "apikey":SUPABASE_KEY,"Authorization":`Bearer ${SUPABASE_KEY}`
    }});
    const attendanceData=await attendanceRes.json();

    if(!attendanceData.length){
        await fetch(`${SUPABASE_URL}/attendance`,{method:'POST',headers:{
            "apikey":SUPABASE_KEY,"Authorization":`Bearer ${SUPABASE_KEY}`,"Content-Type":"application/json"
        },body:JSON.stringify({user_id:employeeId,date:today,time_in:new Date().toISOString(),location:location,source:"qr"})});
        showToast("Checked in successfully!","success");
    }else{
        await fetch(`${SUPABASE_URL}/attendance?id=eq.${attendanceData[0].id}`,{method:'PATCH',headers:{
            "apikey":SUPABASE_KEY,"Authorization":`Bearer ${SUPABASE_KEY}`,"Content-Type":"application/json"
        },body:JSON.stringify({time_out:new Date().toISOString()})});
        showToast("Checked out successfully!","success");
    }
    fetchAttendance();
}

function exportCSV(){
    const rows=Array.from(document.querySelectorAll("#attendanceTable tr"));
    let csv=rows.map(r=>Array.from(r.querySelectorAll("th,td")).map(c=>`"${c.textContent}"`).join(",")).join("\n");
    const link=document.createElement("a");
    link.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);
    link.download='my_attendance.csv';
    link.click();
}

function showToast(message,type="info"){
    const toast=document.createElement("div");
    toast.textContent=message;
    toast.style.position="fixed"; toast.style.top="20px"; toast.style.right="20px";
    toast.style.padding="10px 20px"; toast.style.background=type==="success"?"green":"red";
    toast.style.color="white"; toast.style.borderRadius="5px"; toast.style.zIndex=1000;
    document.body.appendChild(toast);
    setTimeout(()=>document.body.removeChild(toast),3000);
}

startScanner();
