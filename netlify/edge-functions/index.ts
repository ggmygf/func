//import { WebSocket } from "jsr:@std/ws@0.218.2";
//import { serve } from "https://deno.land/std@0.218.2/http/server.ts";

const u = "6af3b37a-91a9-4773-8d76-1e81918448c3"; // User ID for authentication (required)
const p = "bestproxy.onecf.eu.org"; // Proxy IP (optional)
const o = 1; /*WebSocket.OPEN*/ const c = 2; // WebSocket.CLOSING
const ml = console.log; let chass = 11;


async function h(r: Request) {
  if (r.headers.get("upgrade") !== "websocket") {return new Response("Expet...", { status: 426 });}
  const { socket: w, response: y } = Deno.upgradeWebSocket(r);          
  let d = "", e = "";    const l = (i: string, j?: string) => console.log(`[\${d}\:${e}] ${i}`, j || "");
  const h = r.headers.get("sec-websocket-protocol") || "";  
  let v = { value: null as Deno.Conn | null }, g: ((k: Uint8Array) => void) | null = null, n = false;

  try { w.onmessage = async(x)=>{      const b = x.data as Uint8Array;  ml("on_msg"); ml(b_(b, u)); 

      if(n && g) {return g(b);}          if(v.value){await v.value.write(b); return;}    
      const { hasError: f, message: m, portRemote: q = 443, addressRemote: a, rawDataIndex: z, vlessVersion: t = new Uint8Array([0, 0]), isUDP: s } = b_(b, u);

      d = a; e = `<span class="math-inline">\{q\}\-\-</span>{Math.random()} ${s ? "udp " : "tcp "} `;      if (f) {throw new Error(m);}
      if(s){if(q===53) {n=true;} else { throw new Error("UDP go 53 only!"); } }
      const r = new Uint8Array([t[0], 0]), C = b.slice(z);           if (n) { g = (await C_(w, r, l)).write; g(C); return; }

      const D = async (A: string, B: number) => { ml(A); try{
                  const S = await Deno.connect({ hostname: A, port: B }); 
                  if(S){v.value = S; await S.write(C); return S; } 
                };};
      
      // t.close?? 
      const E = async () => {const T=await D(a, q); T.close(); F(T, w, r, null, l);};         const G = await D(p || a, q); F(G, w, r, E, l); 
      const { earlyData: j, error: i } = H(h);         if (i){throw i;} else if(j) {w.send(j);}
             
      };   //asyncx          
         w.onclose= ()=>k(w); w.onerror= (z)=>{l("ws error"); ml(z); k(w);};
 }catch(err) {ml(err);k(w);};    
    
    return y;
}


  //vlessheader
function b_(b: Uint8Array, u: string) {         if (b.byteLength < 24) {return{f: true, m: "header too short"};}
  
  const t = b.slice(0, 1), i = true/*stringify(b.slice(1, 17)) === u*/, 
        s = b.slice(17, 18)[0], 
        c = b.slice(18 + s, 19 + s)[0];

  if (!i) { return { f: true, m: "Invalid user ID" }; }  ml(chass + ":"); console.log(b); chass = chass +1;
  if (c === 2) { console.log("2!!!!!"); return { f: false, q: new DataView(b.slice(19 + s, 21 + s)).getUint16(0), a: new TextDecoder().decode(b.slice(22 + s, 22 + s + b.slice(21 + s, 22 + s)[0])), z: 22 + s + b.slice(21 + s, 22 + s)[0], t, s: true }; }
  if (c === 1) { console.log("1!!!!!"); return { f: false, q: new DataView(b.slice(19 + s, 21 + s)).getUint16(0), a: b.slice(22 + s, 26 + s).join("."), z: 26 + s, t, s: false }; }
  if (c === 3) { console.log("3!!!!!"); return { f: false, q: new DataView(b.slice(19 + s, 21 + s)).getUint16(0), a: Array.from(new DataView(b.slice(22 + s, 38 + s))).filter((_, i) => i % 2 === 0).map((_, i) => new DataView(b.slice(22 + s, 38 + s)).getUint16(i * 2).toString(16)).join(":"), z: 38 + s, t, s: false }; }
  return { f: true, m: `wtf :( ${c}` };
}

// remotesock
async function F(r: Deno.Conn, w: WebSocket, t: Uint8Array, e: (() => Promise<void>) | null, l: (i: string) => void) {try{ 
  for await(const b of r.readable) {
    if(w.readyState!==o){throw new Error("WS-closed");}       const v = t ? new Uint8Array([...t, ...b]) : b;   w.send(v);   t = null;
  } l("Remo-conn-clos");

  }catch(err){console.error("Conn-err:", err);k(w);if(e){l("Retr-conn..."); await e();}}
}

//udp
async function C_(w: WebSocket, t: Uint8Array, l: (i: string) => void) {  let v = false;
  const s = new TransformStream({ transform: (b, c) => { for (let i = 0; i < b.byteLength;) { const l = new DataView(b.slice(i, i + 2)).getUint16(0), d = b.slice(i + 2, i + 2 + l); i += 2 + l; c.enqueue(d); } } });
  
  s.readable.pipeTo(new WritableStream({ 
    async write(b) { const r = await fetch('https://1.1.1.1/dns-query', { method: 'POST', headers: { 'content-type': 'application/dns-message' }, body: b }), 
                           d = await r.arrayBuffer(), 
                           e = new Uint8Array([(d.byteLength >> 8) & 0xff, d.byteLength & 0xff]); 
                           w.readyState === o && (l(`DNS-que-success (${d.byteLength} bit.)`), v ? w.send(new Uint8Array([...e, ...d])) : (w.send(new Uint8Array([...t, ...e, ...d])), v = true)); 
                   }  })).catch((e) => l(`DNS-que-err: ${e}`));

  return { write: (b: Uint8Array) => s.writable.getWriter().write(b) };
}

//base64
function H(h: string | null) {    if(!h){return{error:null};}
  try { h = h.replace(/-/g, "+").replace(/_/g, "/"); const d = atob(h), b = Uint8Array.from(d, (c) => c.charCodeAt(0)); return { earlyData: b.buffer, error: null }; } catch (e) { return { error: e }; }
}

// safeclose
function k(w: WebSocket) { try { (w.readyState === o || w.readyState === c) && w.close(1000, "Normal Closure"); } catch (e) { console.error("WebSocket close error:", e); } }

function stringify(arr: Uint8Array): string {return Array.from(arr).map((byte) => String.fromCharCode(byte)).join("");}

Deno.serve(h);
