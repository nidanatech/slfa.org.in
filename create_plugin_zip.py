
import os
import zipfile
import sys

def create_zip(source_dir, output_filename):
    print(f"Zipping {source_dir} -> {output_filename}")
    source_dir = os.path.abspath(source_dir)
    parent_dir = os.path.dirname(source_dir)
    
    file_count = 0
    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            for file in files:
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, parent_dir).replace('\\', '/')
                print(f"  Adding: {arcname}")
                zipf.write(file_path, arcname)
                file_count += 1
    
    print(f"Total files added: {file_count}")

if __name__ == "__main__":
    base_dir = os.path.abspath(os.path.join("public", "wordpress-plugin"))
    source = os.path.join(base_dir, "sydkic-chat")
    dest = os.path.join(base_dir, "sydkic-chat.zip")
    
    if not os.path.exists(source):
        print(f"Error: Source directory {source} does not exist!")
        sys.exit(1)
        
    create_zip(source, dest)
    print("Done.")
