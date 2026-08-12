document.addEventListener('DOMContentLoaded',()=>{
 const input=document.querySelector('input[name="recipe_image"]');
 if(!input) return;
 input.addEventListener('change',e=>{
   const file=e.target.files[0];
   if(!file) return;
   const img=document.getElementById('preview');
   img.src=URL.createObjectURL(file);
   img.style.display='block';
 });
});
