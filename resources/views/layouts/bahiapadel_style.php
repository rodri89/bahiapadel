<style type="text/css">    

@font-face {
  font-family: "Blender Pro";
  src: url("/bahiapadel/fonts/BlenderPro-Bolditalic.ttf") format("truetype");
  font-weight: normal;
  font-style: normal;
}
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.body_admin {
    color: rgb(0, 0, 0);
}

body {
    color: rgb(255, 255, 255);
    font-family: "Blender Pro", sans-serif;
    font-weight: 400;
    background-color: rgb(26, 26, 26);
    overflow-x: hidden;
}

.wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header_ic {
    width: 180px; 
    height: 80px;
    margin-left: 200px;
}

.header_btn {
  background: transparent !important;
  color: #333 !important;
  border: none !important;
  transition: background 0.2s, color 0.2s;
  border-radius: 35px;
  font-size: 20px;  
}

.header_btn:hover, .header_btn:focus {
  background: #ff0264 !important;
  color: #fff !important;
}

main {
  flex: 1;
}

/* Opcional: para que el footer no tenga margen arriba */
.sticky-footer {
  margin-top: 0;
}

.custom-header {
  background: transparent !important;
  box-shadow: none;
  border: none;
}

.menu-blanco {
  background: #fff;
  border-radius: 35px;
  border: 2px solid #ccc;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  padding: 0.3rem 1rem;
  display: flex;
  flex-wrap: wrap;
  margin-right:200px;
}


.torneo-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    margin: 15px;
    min-width: 300px;    
    max-width: 1100px; 
    color: #222;
}
.torneo-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
    border: 1px solid #ff0264;
    cursor: pointer;
}
.torneo-card .categoria {
    font-weight: bold;
    color: #ff0264;
}
.torneo-card .fechas {
    font-size: 0.95rem;
    color: #555;
}

@media (max-width: 767.98px) {
    .menu-blanco {
    max-width: 80vw;
    margin-left: auto;
    margin-right: 10px;
  }
  .header_ic {
    width: 120px; 
    height: 60px;
    margin-left: 20px;
}
}

/* Hamburguesa blanca */
.navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
}

</style>