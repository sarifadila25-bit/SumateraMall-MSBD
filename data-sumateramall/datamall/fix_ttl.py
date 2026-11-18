with open('toko_plaza_medan_fair.ttl', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix double quotes
content = content.replace('"" ;', '" ;')

with open('toko_plaza_medan_fair_fixed.ttl', 'w', encoding='utf-8') as f:
    f.write(content)

print("File berhasil diperbaiki!")